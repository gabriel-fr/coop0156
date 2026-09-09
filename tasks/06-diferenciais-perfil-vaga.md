# 6. Diferenciais Alinhados ao Perfil da Vaga

Este arquivo **não vem do README do desafio** — é um mapeamento dos "Requisitos
Obrigatórios" e "Diferenciais" da vaga (colados na conversa) para ações concretas dentro
deste projeto, para usar o desafio como vitrine do perfil pedido. Nenhum item aqui é
obrigatório para a nota do desafio; fazer só depois das tarefas 1-4 estarem completas e
testadas. Corresponde à seção "⭐ Diferencial Opcional — Vá Além" do README principal.

## Já cobertos naturalmente pelo desafio (nada extra a fazer)

- PHP / Laravel / arquitetura MVC / APIs REST / SQL / banco relacional → tarefas 1-4.
- JavaScript moderno (ES6+) → tarefas 3 (fetch, async/await, manipulação de DOM).
- HTML5/CSS3 → já entregues no scaffold (Blade + Tailwind).
- Tailwind CSS (citado como diferencial do ecossistema Laravel) → já usado nas views.

## Requer ação deliberada

### Git e GitLab
- [ ] Commits pequenos e descritivos ao longo do desenvolvimento (o README pede
  isso explicitamente na seção "Como Entregar" — não é só para a vaga, é critério de
  avaliação do próprio desafio).
- [ ] Se o repositório final for hospedado no GitLab (a vaga cita GitLab
  especificamente, o README aceita GitHub ou GitLab), considerar abrir Merge Requests
  por etapa (ex.: um MR por tarefa deste plano) em vez de commitar direto na branch
  principal, para deixar histórico de review.

### DevOps / CI-CD
- [ ] Adicionar um pipeline simples (`.gitlab-ci.yml` ou `.github/workflows/tests.yml`,
  conforme onde for hospedado) rodando `composer install` + `php artisan test` a cada
  push. Não precisa de deploy automatizado — só demonstrar o conceito de CI.

### Livewire / Alpine.js
- [ ] Opcional e não obrigatório (o README já resolveu o frontend com JS puro e as
  views são só 2 telas simples) — mas se quiser demonstrar Livewire/Alpine, o melhor
  lugar é implementar a tela de cadastro/listagem de clientes citada como diferencial no
  README (seção "Vá Além"), como um componente Livewire, em vez de reescrever
  `analise.blade.php`/`simulacao.blade.php` que já têm JS funcional pedido explicitamente.

### Python para automação
- [ ] Não há um requisito do desafio que peça Python — não forçar isso dentro do
  Laravel. Se quiser demonstrar essa habilidade dentro do mesmo repositório sem poluir
  o escopo do desafio, uma opção de baixo risco é um script utilitário fora do fluxo
  principal, ex.: `scripts/seed_stress_test.py` que bate no endpoint
  `/api/analise-credito` N vezes com CPFs terminados em cada dígito (1-6 e "outro") para
  gerar massa de dados de teste manual — documentar como rodar no README próprio da
  entrega. Deixar claro que é uma ferramenta auxiliar, não parte da aplicação.

### Ambientes cloud (Azure/AWS/GCP)
- [ ] Fora de escopo para rodar de fato (o desafio roda local via Sail). Se quiser
  citar conhecimento, basta uma nota no README próprio da entrega (seção "decisões
  técnicas") sobre como o projeto poderia ser deployado (ex.: Laravel + banco
  gerenciado + fila gerenciada em algum provedor) — não implementar infra real para
  este desafio.

### Metodologias ágeis (Scrum/Kanban)
- [ ] A própria pasta `tasks/` deste plano já funciona como um backlog simples
  (numerado = ordem de prioridade). Se quiser reforçar isso na entrega, mencionar no
  README próprio que o desenvolvimento seguiu um backlog quebrado em etapas
  incrementais com commits por etapa — não precisa de ferramenta externa (Jira/Trello)
  para um desafio individual.

## Cuidado

Nenhum item desta lista deve atrasar ou arriscar as tarefas obrigatórias (1-4) e a
entrega dentro do prazo de uma semana. Em caso de conflito de tempo, priorize sempre
[07-entrega-checklist.md](07-entrega-checklist.md).
