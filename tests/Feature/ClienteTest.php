<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private function dadosCliente(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'renda_mensal' => 3000.00,
        ], $overrides);
    }

    public function test_criacao_de_cliente_com_dados_validos_retorna_201(): void
    {
        $response = $this->postJson('/api/clientes', $this->dadosCliente());

        $response->assertStatus(201);
        $response->assertJsonPath('cpf', '12345678901');

        $this->assertDatabaseHas('clientes', ['cpf' => '12345678901']);
    }

    public function test_falha_de_validacao_sem_campos_obrigatorios_retorna_422(): void
    {
        $response = $this->postJson('/api/clientes', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nome', 'cpf', 'email', 'renda_mensal']);
    }

    public function test_falha_ao_criar_cliente_com_cpf_duplicado_retorna_422(): void
    {
        Cliente::create($this->dadosCliente());

        $response = $this->postJson('/api/clientes', $this->dadosCliente([
            'email' => 'outro@example.com',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cpf']);
    }

    public function test_falha_ao_criar_cliente_com_email_duplicado_retorna_422(): void
    {
        Cliente::create($this->dadosCliente());

        $response = $this->postJson('/api/clientes', $this->dadosCliente([
            'cpf' => '98765432100',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_listagem_paginada_de_clientes_retorna_200(): void
    {
        Cliente::factory()->count(3)->create();

        $response = $this->getJson('/api/clientes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'current_page',
            'per_page',
            'total',
        ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_exibicao_de_cliente_existente_por_id_retorna_200(): void
    {
        $cliente = Cliente::create($this->dadosCliente());

        $response = $this->getJson("/api/clientes/{$cliente->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $cliente->id);
    }

    public function test_busca_de_cliente_inexistente_retorna_404(): void
    {
        $response = $this->getJson('/api/clientes/999999');

        $response->assertStatus(404);
    }

    public function test_atualizacao_de_cliente_existente_retorna_200(): void
    {
        $cliente = Cliente::create($this->dadosCliente());

        $response = $this->putJson("/api/clientes/{$cliente->id}", $this->dadosCliente([
            'nome' => 'João Atualizado',
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('nome', 'João Atualizado');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'João Atualizado',
        ]);
    }

    public function test_remocao_de_cliente_existente_retorna_204_sem_body(): void
    {
        $cliente = Cliente::create($this->dadosCliente());

        $response = $this->deleteJson("/api/clientes/{$cliente->id}");

        $response->assertStatus(204);
        $response->assertNoContent();

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_remocao_de_cliente_inexistente_retorna_404(): void
    {
        $response = $this->deleteJson('/api/clientes/999999');

        $response->assertStatus(404);
    }
}
