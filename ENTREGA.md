# Entrega — Coop0156 Desafio Técnico

README próprio da entrega, conforme pedido na seção "📤 Como Entregar" do
[README.md](README.md) original. Instruções de setup/execução continuam no README
principal — aqui documento apenas o que foi feito, o que ficou de fora e as decisões
técnicas.

## O que foi implementado

### 1. CRUD de Clientes
`ClienteController` completo (`index`, `store`, `show`, `update`, `destroy`), com
validação via Form Request (`StoreClienteRequest` / `UpdateClienteRequest`):
CPF único de 11 dígitos, e-mail único, `renda_mensal` numérica positiva. Listagem
paginada (`Cliente::paginate`), 404 automático via `findOrFail` e `204` sem corpo na
remoção.

### 2. Integração com o Bureau e regras de negócio
Lógica isolada em `App\Services\AnaliseCreditoService` (nada de regra de negócio no
controller). Fluxo: valida entrada → localiza ou cria o cliente pelo CPF
(`firstOrCreate`) → persiste a análise como `pendente` → consulta
`GET /api/mock/bureau/{cpf}` via `Http::` → aplica as faixas de score/renda/
comprometimento → atualiza a análise.

Resiliência: qualquer falha da chamada HTTP (erro 5xx, `ConnectionException`, ou
resposta sem a chave `score`) é tratada no mesmo lugar e resulta em análise
`reprovado` com motivo `"Falha na consulta ao Bureau de Crédito"` — nunca um 500 cru
ou exception não tratada.

### 3. Tela de simulação e contratação
JavaScript implementado em `analise.blade.php` (submissão do formulário, exibição do
resultado aprovado/reprovado, redirecionamento para `/simulacao/{id}` quando
aprovado) e em `simulacao.blade.php` (botão "Confirmar Contratação" chamando
`POST /api/analise-credito/{id}/contratar`, com spinner, tratamento de erro e modal
de sucesso).

### 4. Testes automatizados
- `tests/Feature/AnaliseCreditoTest.php`: aprovação com score alto (taxa 2,9%) e
  score médio (taxa 4,5%), reprovação por renda insuficiente / score baixo /
  comprometimento de renda, falha 500 do Bureau, resposta malformada (sem `score`),
  criação automática de cliente por CPF novo, contratação aprovada (despacha o Job),
  contratação de análise não aprovada (422) e de análise inexistente (404), e o
  `ProcessarContratacaoJob::handle()` isolado.
- `tests/Feature/ClienteTest.php` (criado do zero): os 10 cenários do CRUD (criação,
  validação, CPF/e-mail duplicado, listagem paginada, exibição, 404, atualização,
  remoção, remoção de inexistente).
- `database/factories/ClienteFactory.php` criada para dar suporte aos testes.
- Suíte completa rodando 100% via `php artisan test` (23 testes, ~68 assertions).

### ⭐ Diferencial — Filas (Laravel Queues)
Implementado conforme sugerido no README: `contratar` agora atualiza a análise para
`processando_contratacao` e despacha `ProcessarContratacaoJob`, que finaliza a
contratação (`contratado`) e registra log de sucesso. Testado com `Queue::fake()` +
`assertPushed`, além de teste direto do `handle()` do Job, e validado manualmente
ponta a ponta com `queue:work --once`.

### ⭐ Diferencial — CI
Pipeline no GitHub Actions ([.github/workflows/tests.yml](.github/workflows/tests.yml))
rodando `composer install` + `php artisan test` a cada push/PR em `main`.

## O que ficou de fora

- **Tela de cadastro/listagem de clientes (Livewire/Alpine ou qualquer outra
  stack)** — citada como diferencial opcional no README ("Vá Além"). Não implementada
  por não ser exigida e para manter o escopo focado nas 4 etapas obrigatórias.
- **Script utilitário em Python** (ex.: gerador de massa de teste) — não fazia
  sentido dentro do escopo Laravel do desafio.
- **Nenhuma nota de deploy em cloud** foi adicionada — o projeto roda localmente via
  Sail, deploy real não fazia parte do pedido.
- **`.env.example` não foi atualizado** para o valor de `SCORE_BUREAU_API_URL` usado
  de fato com Sail (`http://localhost/api/mock/bureau`, sem porta — a chamada é feita
  de dentro do container, que expõe a aplicação na porta 80 internamente). O valor
  publicado no `.env.example` (`http://localhost:8000/...`) é o correto apenas para a
  Opção B (PHP local com `php artisan serve`). Vale ajustar antes de outra pessoa
  rodar via Sail.

## Decisões técnicas relevantes

- **Lógica de negócio em Service, não no Controller** — `AnaliseCreditoService`
  concentra a orquestração (busca/criação de cliente, chamada ao Bureau, aplicação
  das regras). O controller só valida a entrada (Form Request) e devolve a resposta.
- **Cliente sem e-mail no fluxo de análise** — a tela de análise não coleta e-mail,
  mas a tabela `clientes` exige um valor único. Ao criar automaticamente o cliente
  via `firstOrCreate`, é gerado um placeholder determinístico
  (`cliente-{cpf}@pendente.coop0156.local`). Cliente já existente não tem seus dados
  sobrescritos pela nova solicitação.
- **Falhas do Bureau tratadas em um único ponto** — `consultarBureau()` retorna
  `null` para qualquer cenário de falha (HTTP 5xx, exceção de conexão, corpo sem
  `score`), e quem chama decide o que fazer com isso (reprovar com motivo claro),
  evitando duplicar tratamento de erro pela aplicação.
- **Contratação assíncrona** — em vez de contratar de forma síncrona, o status
  intermediário `processando_contratacao` deixa claro pro cliente da API que a
  finalização acontece em segundo plano, e o Job é responsável só por essa etapa
  final (fácil de re-executar/observar via log em caso de falha).

## Como rodar / testar

Ver seção "🚀 Como Executar o Projeto" no [README.md](README.md) principal (Opção A —
Sail, recomendada, ou Opção B — PHP local). Para rodar a suíte de testes:

```bash
./vendor/bin/sail artisan test
# ou
php artisan test
```
