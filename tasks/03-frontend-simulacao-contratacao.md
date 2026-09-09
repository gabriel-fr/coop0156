# 3. Tela de Simulação e Contratação (JavaScript)

Depende da tarefa [02](02-bureau-regras-negocio.md) já estar retornando o JSON da
análise com os campos usados aqui.

## 3.1 `resources/views/analise.blade.php`

Bloco `<script>` no final do arquivo (linhas ~266-274).

- [ ] Capturar `submit` do `#form-analise`, prevenir default.
- [ ] Mostrar spinner (`#loading-spinner`) e desabilitar `#btn-solicitar` durante a
  requisição.
- [ ] Montar payload com `nome`, `cpf` (limpar máscara, mandar só dígitos),
  `renda_mensal`, `tipo_credito`, `valor_solicitado`.
- [ ] `fetch('/api/analise-credito', { method: 'POST', headers: {'Content-Type':
  'application/json', Accept: 'application/json'}, body: JSON.stringify(payload) })`.
- [ ] Esconder `#resultado-vazio`, mostrar `#resultado-analise`.
- [ ] Preencher `#res-nome`, `#res-cpf`, `#res-score`, `#res-status` com a resposta.
- [ ] Se `status === 'aprovado'`:
  - Mostrar `#dados-aprovado` (preencher `#res-taxa`, `#res-parcela`,
    `#res-comprometimento`).
  - Mostrar `#container-contratacao` com um link/botão para `/simulacao/{id}`
    (o botão `#btn-contratar` deste arquivo pode simplesmente navegar para a URL —
    a contratação de fato acontece na tela de simulação, tarefa 3.2).
- [ ] Se `status === 'reprovado'`:
  - Mostrar `#dados-reprovado`, preencher `#res-motivo` com `motivo_rejeicao`.
  - Manter `#container-contratacao` escondido.
- [ ] Tratar erro de rede/validação (ex. 422) exibindo mensagem amigável (pode reusar o
  bloco `#dados-reprovado` ou um alerta simples).
- [ ] Restaurar botão/spinner ao final (`finally`).

## 3.2 `resources/views/simulacao.blade.php`

Bloco `<script>` no final do arquivo (linhas ~232-237). View já é renderizada
server-side com os dados da análise (`$analise`) vindos de `SimulacaoController`
(conferir se esse controller já busca a análise por `id` — implementar se necessário).

- [ ] Capturar `click` no `#btn-confirmar`.
- [ ] Mostrar spinner (`#spinner-confirmar`), desabilitar o botão.
- [ ] `fetch('/api/analise-credito/{{ $analise->id }}/contratar', { method: 'POST',
  headers: {Accept: 'application/json'} })`.
- [ ] Sucesso (200): exibir `#modal-sucesso`.
- [ ] Erro (404/422/500): exibir mensagem de erro clara para o usuário (ex. reaproveitar
  o bloco de `session('erro')` já presente na view, ou um alerta simples via JS).
- [ ] Reabilitar botão/spinner em caso de erro.

## `SimulacaoController` (já implementado — não precisa mexer)

[app/Http/Controllers/SimulacaoController.php](../app/Http/Controllers/SimulacaoController.php)
já faz `findOrFail`, checa `status === StatusAnalise::APROVADO` e redireciona para `/`
com `session('erro')` caso contrário — é por isso que `analise.blade.php` só deve linkar
para `/simulacao/{id}` quando a análise vier aprovada (ver 3.1). Nenhuma ação necessária
aqui, só ter isso em mente ao testar o fluxo manual.

## Critério de aceite

Fluxo manual completo funcionando no navegador: preencher formulário → ver resultado →
(se aprovado) ir para simulação → confirmar contratação → ver modal de sucesso.
