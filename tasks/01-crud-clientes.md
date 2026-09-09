# 1. CRUD de Clientes

Arquivo alvo: [app/Http/Controllers/ClienteController.php](../app/Http/Controllers/ClienteController.php)
Rotas já registradas via `Route::apiResource('clientes', ClienteController::class)` em
[routes/api.php](../routes/api.php) — não precisa mexer nas rotas.

## Tarefas

- [ ] Criar Form Requests para validação (boa prática pedida no README):
  - `app/Http/Requests/StoreClienteRequest.php`
  - `app/Http/Requests/UpdateClienteRequest.php`
  - Regras:
    - `nome`: `required|string`
    - `cpf`: `required|digits:11|unique:clientes,cpf` (ignorar o próprio registro no update)
    - `email`: `required|email|unique:clientes,email` (idem ignore no update)
    - `telefone`: `nullable|string`
    - `renda_mensal`: `required|numeric|min:0`
- [ ] `index()`: retornar `Cliente::paginate()` (definir um tamanho de página razoável,
  ex. 15) como JSON.
- [ ] `store(StoreClienteRequest $request)`: criar o cliente com `Cliente::create($request->validated())`,
  retornar `201` com o recurso criado.
- [ ] `show($id)`: usar `Cliente::findOrFail($id)` (ou route model binding) para obter o
  404 automático do Laravel; retornar o recurso.
- [ ] `update(UpdateClienteRequest $request, $id)`: `findOrFail` + `update($request->validated())`,
  retornar `200` com o recurso atualizado.
- [ ] `destroy($id)`: `findOrFail` + `delete()`, retornar `204` sem corpo
  (`response()->noContent()`).
- [ ] Garantir que erros de validação retornem `422` com mensagens claras (comportamento
  padrão do Laravel ao usar Form Request — só não capturar a exceção).

## Critérios de aceite (ligados aos testes de `ClienteTest`, ver [04](04-testes-automatizados.md))

- `POST /api/clientes` com dados válidos → `201`.
- `POST /api/clientes` sem campos obrigatórios → `422`.
- `POST /api/clientes` com CPF duplicado → `422`.
- `POST /api/clientes` com e-mail duplicado → `422`.
- `GET /api/clientes` → `200`, paginado.
- `GET /api/clientes/{id}` existente → `200`.
- `GET /api/clientes/{id}` inexistente → `404`.
- `PUT /api/clientes/{id}` existente (atualização parcial ou completa) → `200`.
- `DELETE /api/clientes/{id}` existente → `204` sem body.
- `DELETE /api/clientes/{id}` inexistente → `404`.

## Observação

Não existe tela própria de cadastro de clientes na interface — este CRUD é validado só
via API/testes automatizados (ver nota no README principal, seção 2).
