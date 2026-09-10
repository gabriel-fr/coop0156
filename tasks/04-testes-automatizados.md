# 4. Testes Automatizados

Rodar com `./vendor/bin/sail artisan test` (ou `php artisan test` na Opção B). Usar
sempre `Http::fake()` — nunca bater na rota mock de verdade nos testes (evita o `sleep(5)`
do cenário de timeout deixar a suíte lenta).

## 4.1 `tests/Feature/AnaliseCreditoTest.php`

Arquivo já existe com um teste-guia (`test_rota_solicitar_analise_retorna_stub_nao_implementado`,
[linha 19](../tests/Feature/AnaliseCreditoTest.php#L19)) que hoje espera `501`. Esse
teste deve ser **substituído/reescrito** assim que `solicitar` for implementado (vai
quebrar naturalmente, é esperado).

Cenários a cobrir (do próprio README):

- [x] Aprovação com score alto (CPF terminado em `3` → score 850) → status `aprovado`,
  taxa `2.9`.
- [x] Aprovação com score médio (CPF terminado em `2` → score 550) → status `aprovado`,
  taxa `4.5`.
- [x] Reprovação por renda insuficiente (`renda_mensal < 1500`) — não precisa nem
  mockar score corretamente, a regra deve barrar antes.
- [x] Reprovação por score baixo (CPF terminado em `1` → score 150).
- [x] Reprovação por comprometimento de renda (valor solicitado alto o suficiente para
  que a parcela some mais de 30% da renda, mesmo com score aprovador).
- [x] Falha da API do Bureau (`Http::fake(['*' => Http::response(['error' => '...'],
  500)])`) → resposta limpa da aplicação (sem 500 "cru", sem exception não tratada).
- [x] Confirmação de contratação (`contratar`) com análise `aprovado` → muda para
  `contratado` (ou `processando_contratacao` se implementar o diferencial de fila, ver
  [05](05-diferencial-filas.md)).
- [x] Criação automática do cliente ao solicitar análise com CPF novo — assert que
  `Cliente::where('cpf', ...)->exists()` após a chamada.
- [x] (Extra recomendado) `contratar` em análise que não está `aprovado` → erro
  claro (422/409), não deve mudar o status.
- [x] (Extra recomendado) `contratar` em análise inexistente → `404`.
- [x] (Extra recomendado) resposta malformada do Bureau (sem chave `score`) → mesma
  resiliência do cenário de erro 500.

Usar `Http::fake()` por CPF/URL, ex.:
```php
Http::fake([
    'localhost:8000/api/mock/bureau/*' => Http::response(['cpf' => '...', 'score' => 850]),
]);
```

## 4.2 `tests/Feature/ClienteTest.php` (criar do zero)

- [x] Criação de cliente com dados válidos → `201`.
- [x] Falha de validação sem campos obrigatórios → `422`.
- [x] Falha com CPF duplicado → `422`.
- [x] Falha com e-mail duplicado → `422`.
- [x] Listagem paginada → `200` (assert estrutura de paginação, ex. chave `data`).
- [x] Exibição por ID existente → `200`.
- [x] `404` ao buscar ID inexistente.
- [x] Atualização parcial de cliente existente → `200`.
- [x] Remoção de cliente existente → `204` sem body.
- [x] `404` ao remover ID inexistente.

Usar `RefreshDatabase` (já usado em `AnaliseCreditoTest`) e, se útil, uma factory para
`Cliente` (`database/factories/ClienteFactory.php` — não existe ainda, criar se for
facilitar a escrita dos testes).

## Rodando

```bash
./vendor/bin/sail artisan test
# ou, com PHP local:
php artisan test
```
