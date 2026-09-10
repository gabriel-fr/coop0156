<?php

namespace Tests\Feature;

use App\Enums\StatusAnalise;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

    private function dadosSolicitacao(array $overrides = []): array
    {
        return array_merge([
            'cpf' => '12345678901',
            'nome' => 'João da Silva',
            'renda_mensal' => 3000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
        ], $overrides);
    }

    public function test_aprovacao_com_score_alto_aplica_taxa_de_2_9(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678903', 'score' => 850]),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678903',
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::APROVADO->value);
        $response->assertJsonPath('score', 850);
        $response->assertJsonPath('taxa_juros', '2.90');
    }

    public function test_aprovacao_com_score_medio_aplica_taxa_de_4_5(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678902', 'score' => 550]),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678902',
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::APROVADO->value);
        $response->assertJsonPath('score', 550);
        $response->assertJsonPath('taxa_juros', '4.50');
    }

    public function test_reprovacao_por_renda_insuficiente_nao_consulta_regra_de_score(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678903', 'score' => 850]),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678903',
            'renda_mensal' => 1000.00,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::REPROVADO->value);
        $response->assertJsonPath('motivo_rejeicao', 'Renda mínima insuficiente');
    }

    public function test_reprovacao_por_score_baixo(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678901', 'score' => 150]),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678901',
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::REPROVADO->value);
        $response->assertJsonPath('motivo_rejeicao', 'Score de crédito muito baixo');
    }

    public function test_reprovacao_por_comprometimento_de_renda(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678903', 'score' => 850]),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678903',
            'renda_mensal' => 1600.00,
            'valor_solicitado' => 50000.00,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::REPROVADO->value);
        $response->assertJsonPath('motivo_rejeicao', 'Comprometimento de renda superior a 30%');
    }

    public function test_falha_da_api_do_bureau_retorna_resposta_limpa_sem_erro_500(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['error' => 'Erro interno na comunicação com o provedor de score.'], 500),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao());

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::REPROVADO->value);
        $response->assertJsonPath('motivo_rejeicao', 'Falha na consulta ao Bureau de Crédito');
    }

    public function test_resposta_malformada_do_bureau_sem_chave_score_e_tratada_com_resiliencia(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678901', 'status_bureau' => 'ok']),
        ]);

        $response = $this->postJson('/api/analise-credito', $this->dadosSolicitacao());

        $response->assertStatus(201);
        $response->assertJsonPath('status', StatusAnalise::REPROVADO->value);
        $response->assertJsonPath('motivo_rejeicao', 'Falha na consulta ao Bureau de Crédito');
    }

    public function test_solicitar_analise_com_cpf_novo_cria_cliente_automaticamente(): void
    {
        Http::fake([
            '*/mock/bureau/*' => Http::response(['cpf' => '12345678903', 'score' => 850]),
        ]);

        $this->assertDatabaseMissing('clientes', ['cpf' => '12345678903']);

        $this->postJson('/api/analise-credito', $this->dadosSolicitacao([
            'cpf' => '12345678903',
        ]))->assertStatus(201);

        $this->assertTrue(Cliente::where('cpf', '12345678903')->exists());
    }

    public function test_contratar_analise_aprovada_muda_status_para_processando_contratacao_e_despacha_job(): void
    {
        Queue::fake();

        $cliente = Cliente::create([
            'nome' => 'Maria Souza',
            'cpf' => '98765432100',
            'email' => 'maria@example.com',
            'renda_mensal' => 5000.00,
        ]);

        $analise = AnaliseCredito::create([
            'cliente_id' => $cliente->id,
            'cpf' => $cliente->cpf,
            'nome' => $cliente->nome,
            'renda_mensal' => $cliente->renda_mensal,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
            'status' => StatusAnalise::APROVADO,
            'score' => 850,
            'taxa_juros' => 2.9,
            'valor_parcela' => 1123.33,
        ]);

        $response = $this->postJson("/api/analise-credito/{$analise->id}/contratar");

        $response->assertStatus(200);
        $response->assertJsonPath('status', StatusAnalise::PROCESSANDO_CONTRATACAO->value);

        $this->assertSame(StatusAnalise::PROCESSANDO_CONTRATACAO, $analise->fresh()->status);

        Queue::assertPushed(ProcessarContratacaoJob::class, function (ProcessarContratacaoJob $job) use ($analise) {
            return $job->analiseId === $analise->id;
        });
    }

    public function test_processar_contratacao_job_finaliza_contratacao(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Maria Souza',
            'cpf' => '98765432100',
            'email' => 'maria@example.com',
            'renda_mensal' => 5000.00,
        ]);

        $analise = AnaliseCredito::create([
            'cliente_id' => $cliente->id,
            'cpf' => $cliente->cpf,
            'nome' => $cliente->nome,
            'renda_mensal' => $cliente->renda_mensal,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
            'status' => StatusAnalise::PROCESSANDO_CONTRATACAO,
            'score' => 850,
            'taxa_juros' => 2.9,
            'valor_parcela' => 1123.33,
        ]);

        (new ProcessarContratacaoJob($analise->id))->handle();

        $this->assertSame(StatusAnalise::CONTRATADO, $analise->fresh()->status);
    }

    public function test_contratar_analise_que_nao_esta_aprovada_retorna_erro_e_nao_altera_status(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Pedro Lima',
            'cpf' => '11122233344',
            'email' => 'pedro@example.com',
            'renda_mensal' => 3000.00,
        ]);

        $analise = AnaliseCredito::create([
            'cliente_id' => $cliente->id,
            'cpf' => $cliente->cpf,
            'nome' => $cliente->nome,
            'renda_mensal' => $cliente->renda_mensal,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
            'status' => StatusAnalise::REPROVADO,
            'motivo_rejeicao' => 'Score de crédito muito baixo',
        ]);

        $response = $this->postJson("/api/analise-credito/{$analise->id}/contratar");

        $response->assertStatus(422);

        $this->assertSame(StatusAnalise::REPROVADO, $analise->fresh()->status);
    }

    public function test_contratar_analise_inexistente_retorna_404(): void
    {
        $response = $this->postJson('/api/analise-credito/999999/contratar');

        $response->assertStatus(404);
    }
}
