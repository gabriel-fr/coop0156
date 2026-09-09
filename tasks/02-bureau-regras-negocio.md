# 2. Integração com o Bureau e Regras de Negócio

Arquivo alvo: [app/Http/Controllers/AnaliseCreditoController.php](../app/Http/Controllers/AnaliseCreditoController.php),
método `solicitar`.

Config já pronta em [config/services.php](../config/services.php) (`services.score_bureau.url`
e `services.score_bureau.timeout`, lidas do `.env`).

> ⚠️ **Corrigir antes de tudo:** o `.env` atual tem
> `SCORE_BUREAU_API_URL=http://localhost:8000/api/mock/score`, mas a rota real do mock é
> `/api/mock/bureau/{cpf}` (ver [routes/api.php](../routes/api.php) e
> [MockBureauController.php](../app/Http/Controllers/MockBureauController.php), **não
> alterar o controller/rota**). Ajustar `SCORE_BUREAU_API_URL` para
> `http://localhost:8000/api/mock/bureau` (sem CPF, que é concatenado na chamada) — do
> contrário toda consulta ao Bureau vai cair em 404.

## Organização do código (critério de avaliação: separar lógica do Controller)

- [ ] Criar `app/Services/AnaliseCreditoService.php` (ou `app/Actions/...`) para
  concentrar: localizar/criar cliente, chamar o Bureau, aplicar regras, persistir
  resultado. Controller deve ficar fino (valida request, chama o service, retorna
  resposta).
- [ ] Criar `app/Http/Requests/SolicitarAnaliseCreditoRequest.php` com validação de:
  `nome` (required|string), `cpf` (required|digits:11), `renda_mensal`
  (required|numeric|min:0), `tipo_credito` (required|in: valores do enum `TipoCredito`),
  `valor_solicitado` (required|numeric|min:0.01).

## Fluxo do `solicitar`

- [ ] 1. Validar entrada (Form Request acima).
- [ ] 2. Localizar ou criar cliente por CPF:
  `Cliente::firstOrCreate(['cpf' => $cpf], ['nome' => ..., 'renda_mensal' => ...])`.
  Garantir que análises sempre fiquem com `cliente_id` preenchido.
- [ ] 3. Criar `AnaliseCredito` com `status = StatusAnalise::PENDENTE` (enum já definido
  em [app/Enums/StatusAnalise.php](../app/Enums/StatusAnalise.php): `PENDENTE`,
  `APROVADO`, `REPROVADO`, `PROCESSANDO_CONTRATACAO`, `CONTRATADO`), vinculada ao
  `cliente_id`, com os dados da solicitação (`cpf`, `nome`, `renda_mensal`,
  `tipo_credito`, `valor_solicitado`).
- [ ] 4. Consultar o Bureau via `Http::timeout(config('services.score_bureau.timeout'))
  ->get(config('services.score_bureau.url') . "/{$cpf}")` (ajustar conforme a URL
  configurada no `.env` — confirmar se a base já inclui `/api/mock/bureau` ou só o host).
- [ ] 5. Tratar cenários de falha (usar `try/catch` de `ConnectionException` +
  checagem de `$response->failed()` / `$response->json('score')` ausente):
  - Timeout / exceção de conexão → resposta de erro controlada (não deixar propagar
    exceção não tratada, não pode ser 500 "cru").
  - HTTP 500 do Bureau → mesma tratativa.
  - JSON sem a chave `score` → tratar como falha de integração.
  - Em qualquer falha: decidir e documentar um comportamento consistente (ex.: marcar
    análise como reprovada com motivo "Falha na consulta ao Bureau de Crédito" ou
    retornar 503/422 claro) — o importante é não travar a aplicação nem vazar stack
    trace.
- [ ] 6. Se o Bureau responder com sucesso, aplicar as regras de crédito na ordem:
  1. `renda_mensal < 1500` → reprovado, motivo "Renda mínima insuficiente".
  2. `score < 400` → reprovado, motivo "Score de crédito muito baixo".
  3. `score` entre 400 e 699 → aprovado, `taxa_juros = 4.5`.
  4. `score >= 700` → aprovado, `taxa_juros = 2.9`.
  5. Calcular parcela (juros simples, 12 parcelas fixas):
     - `juros_totais = valor_solicitado * (taxa/100) * 12`
     - `valor_total = valor_solicitado + juros_totais`
     - `valor_parcela = valor_total / 12`
  6. Se `valor_parcela > renda_mensal * 0.30` → reprovado, motivo "Comprometimento de
     renda superior a 30%" (mesmo que o score tivesse aprovado).
- [ ] 7. Persistir na análise: `score`, `status` final, `taxa_juros` (se aprovado),
  `valor_parcela` (se aprovado), `motivo_rejeicao` (se reprovado).
- [ ] 8. Retornar a análise atualizada como JSON (incluir `id`, `status`,
  `motivo_rejeicao`, `score`, `taxa_juros`, `valor_parcela` — o frontend da tarefa
  [03](03-frontend-simulacao-contratacao.md) depende desses campos).

## Pontos de atenção

- Não são penalizadas pequenas variações de arredondamento — o que importa é a
  aplicação correta das faixas (ver nota no README principal).
- A ordem de verificação importa: renda mínima e score são checados **antes** da
  consulta influenciar a taxa; comprometimento de renda é checado **depois** de já
  saber a taxa aplicável.
- Resiliência do `Http::` é item de avaliação específico — não deixar sem tratamento.
