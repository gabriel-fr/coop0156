# 5. Diferencial Opcional — Filas (Laravel Queues)

Não obrigatório, mas soma pontos e é um requisito comum em vagas Laravel (processamento
assíncrono). `.env` já está com `QUEUE_CONNECTION=database`, então só falta a
implementação.

Arquivos:
- [app/Http/Controllers/AnaliseCreditoController.php](../app/Http/Controllers/AnaliseCreditoController.php)
  (método `contratar`)
- [app/Jobs/ProcessarContratacaoJob.php](../app/Jobs/ProcessarContratacaoJob.php) (stub vazio)

## Tarefas

- [x] No `contratar`: em vez de atualizar direto para `contratado`, atualizar o status
  para `StatusAnalise::PROCESSANDO_CONTRATACAO` e disparar
  `ProcessarContratacaoJob::dispatch($analise->id)`.
- [x] Implementar `ProcessarContratacaoJob::handle()`:
  - Buscar a `AnaliseCredito` pelo `analiseId`.
  - Atualizar status para `StatusAnalise::CONTRATADO`.
  - Registrar log de sucesso (`Log::info(...)`, incluindo o ID da análise).
- [x] Rodar as migrations da tabela `jobs` (já existe migration padrão do Laravel,
  `0001_01_01_000002_create_jobs_table.php` — conferir que já rodou).
- [x] Rodar o worker em terminal separado: `./vendor/bin/sail artisan queue:work` (ou
  `php artisan queue:work` na Opção B).
- [x] Ajustar teste de contratação em `AnaliseCreditoTest` (ver [04](04-testes-automatizados.md))
  para usar `Queue::fake()` e fazer assert de
  `Queue::assertPushed(ProcessarContratacaoJob::class)`, além de conferir que o status
  imediatamente após a chamada HTTP é `processando_contratacao` (o job não roda de
  fato durante o teste com `Queue::fake()`).
- [x] Frontend: a tela `analise.blade.php` já tem um card pronto para esse cenário
  (`#card-sucesso-contratacao`, mostrando "Status: PROCESSANDO_CONTRATACAO") — se for
  usado como fluxo de contratação a partir dali, conferir que faz sentido com o que foi
  implementado na tarefa [03](03-frontend-simulacao-contratacao.md). Se a contratação
  ficar só na tela de simulação, avaliar se vale adaptar o modal de sucesso lá para
  refletir "processando" em vez de "contratado" imediato.

## Por que isso importa para o perfil da vaga

Demonstra conhecimento em processamento assíncrono/filas, algo frequentemente cobrado
em vagas Laravel de nível pleno+ — ver também [06](06-diferenciais-perfil-vaga.md).
