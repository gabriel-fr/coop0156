# 7. Checklist Final de Entrega

Baseado na seção "📤 Como Entregar" e "🏆 Critérios de Avaliação" do README principal.

## Antes de entregar

- [ ] Todos os itens obrigatórios (tarefas [01](01-crud-clientes.md)-[04](04-testes-automatizados.md))
  implementados.
- [ ] `./vendor/bin/sail artisan test` (ou `php artisan test`) passando 100%.
- [ ] Fluxo manual completo testado no navegador (formulário → resultado → simulação →
  contratação) — ver [03](03-frontend-simulacao-contratacao.md).
- [ ] Testados manualmente os 7 cenários do mock do Bureau (últimos dígitos de CPF 1-6 e
  um "qualquer outro").
- [ ] `.env` corrigido (`SCORE_BUREAU_API_URL` apontando para `/api/mock/bureau`, ver
  [02](02-bureau-regras-negocio.md)) e `.env.example` atualizado se necessário.
- [ ] Código sem `dd()`, `dump()`, `var_dump` ou comentários `TODO` esquecidos.
- [ ] Histórico de commits com múltiplos commits descritivos (não um único commit
  gigante no final).

## Repositório

- [ ] Repositório público (ou privado com acesso liberado ao avaliador) no GitHub ou
  GitLab pessoal.
- [ ] README próprio da entrega (diferente deste `tasks/`) descrevendo:
  - O que foi implementado.
  - O que ficou de fora, se houver (e por quê).
  - Decisões técnicas relevantes (ex.: como tratou falha do Bureau, estrutura de
    Services/Actions escolhida).
- [ ] (Opcional) mencionar os diferenciais implementados — fila, itens de
  [06](06-diferenciais-perfil-vaga.md), etc.

## Envio

- [ ] Enviar o link do repositório por e-mail para **jonathan_peixoto@sicredi.com.br**
  dentro do prazo de uma semana combinado.

## Revisão cruzada com os Critérios de Avaliação do README

1. Boas práticas de API REST (validações, Form Requests, status codes corretos) →
   coberto em [01](01-crud-clientes.md) e [02](02-bureau-regras-negocio.md).
2. Organização do código (lógica fora do Controller, Services/Actions) →
   [02](02-bureau-regras-negocio.md).
3. Resiliência na integração HTTP (timeouts, erros do Bureau sem crash) →
   [02](02-bureau-regras-negocio.md).
4. Qualidade dos testes (`Http::fake()`, edge cases) → [04](04-testes-automatizados.md).
5. Consistência e clareza (nomenclatura, commits descritivos) → todo o desenvolvimento +
   este checklist.
