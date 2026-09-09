# Plano de Tarefas — Desafio Coop0156

Quebra do desafio descrito em [../README.md](../README.md) em tarefas executáveis, na
ordem recomendada de implementação. Cada arquivo tem checklist + critérios de aceite.

## Ordem recomendada

| # | Arquivo | Bloco | Obrigatório? |
|---|---|---|---|
| 1 | [01-crud-clientes.md](01-crud-clientes.md) | CRUD `ClienteController` | Sim |
| 2 | [02-bureau-regras-negocio.md](02-bureau-regras-negocio.md) | Integração Bureau + regras de crédito | Sim |
| 3 | [03-frontend-simulacao-contratacao.md](03-frontend-simulacao-contratacao.md) | JS de `analise.blade.php` e `simulacao.blade.php` + `contratar` | Sim |
| 4 | [04-testes-automatizados.md](04-testes-automatizados.md) | `AnaliseCreditoTest` + `ClienteTest` | Sim |
| 5 | [05-diferencial-filas.md](05-diferencial-filas.md) | Fila para contratação (`ProcessarContratacaoJob`) | Opcional (diferencial do desafio) |
| 6 | [06-diferenciais-perfil-vaga.md](06-diferenciais-perfil-vaga.md) | Itens que reforçam os requisitos da vaga (Git, CI/CD, Livewire/Alpine, Python, cloud, ágil) | Opcional |
| 7 | [07-entrega-checklist.md](07-entrega-checklist.md) | Checklist final de entrega | Sim |

## Por que essa ordem

- CRUD de clientes primeiro porque `AnaliseCreditoController::solicitar` depende do
  model `Cliente` já validado/persistido (`firstOrCreate` pelo CPF).
- Regras de negócio e integração com o Bureau antes do frontend, porque o JS depende
  do formato de resposta da API (`status`, `motivo_rejeicao`, `taxa_juros`, etc.).
- Testes por último no fluxo de implementação, mas devem ser escritos incrementalmente
  a cada regra implementada — não deixar tudo para o final.
- Diferenciais são bônus (não travam a nota se não implementados) — ver critérios de
  avaliação no README principal.

## Estado atual do scaffold (referência rápida)

- `ClienteController` e `AnaliseCreditoController`: todos os métodos retornam `501 Not
  Implemented` — nada implementado ainda.
- `Cliente` e `AnaliseCredito` (models): relacionamentos e casts já prontos.
- `MockBureauController`: pronto, **não alterar**.
- `resources/views/analise.blade.php` e `simulacao.blade.php`: HTML/CSS prontos, blocos
  de `<script>` vazios com TODOs.
- `app/Jobs/ProcessarContratacaoJob.php`: stub vazio (diferencial).
- `tests/Feature/AnaliseCreditoTest.php`: só tem o teste-guia do stub 501.
- `tests/Feature/ClienteTest.php`: **não existe ainda**, precisa ser criado.
