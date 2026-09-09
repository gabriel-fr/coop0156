<?php

namespace App\Services;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnaliseCreditoService
{
    private const RENDA_MINIMA = 1500.0;
    private const SCORE_MINIMO = 400;
    private const SCORE_TAXA_REDUZIDA = 700;
    private const TAXA_SCORE_MEDIO = 4.5;
    private const TAXA_SCORE_ALTO = 2.9;
    private const COMPROMETIMENTO_MAXIMO = 0.30;
    private const NUMERO_PARCELAS = 12;

    /**
     * Processa uma solicitação de análise de crédito: localiza/cadastra o cliente,
     * consulta o Bureau de Crédito e aplica as regras de elegibilidade.
     *
     * @param  array<string, mixed>  $dados
     */
    public function solicitar(array $dados): AnaliseCredito
    {
        $cpf = preg_replace('/\D/', '', $dados['cpf']);

        // A solicitação não coleta e-mail, mas a tabela de clientes exige um valor
        // único e não-nulo; geramos um placeholder determinístico pelo CPF.
        $cliente = Cliente::firstOrCreate(
            ['cpf' => $cpf],
            [
                'nome' => $dados['nome'],
                'renda_mensal' => $dados['renda_mensal'],
                'email' => "cliente-{$cpf}@pendente.coop0156.local",
            ]
        );

        $analise = AnaliseCredito::create([
            'cliente_id' => $cliente->id,
            'cpf' => $cpf,
            'nome' => $dados['nome'],
            'renda_mensal' => $dados['renda_mensal'],
            'tipo_credito' => $dados['tipo_credito'],
            'valor_solicitado' => $dados['valor_solicitado'],
            'status' => StatusAnalise::PENDENTE,
        ]);

        $score = $this->consultarBureau($cpf);

        if ($score === null) {
            $analise->update([
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Falha na consulta ao Bureau de Crédito',
            ]);

            return $analise;
        }

        $analise->update(array_merge(
            ['score' => $score],
            $this->aplicarRegras(
                (float) $dados['renda_mensal'],
                $score,
                (float) $dados['valor_solicitado']
            )
        ));

        return $analise;
    }

    /**
     * Consulta o Bureau de Crédito para o CPF informado.
     *
     * @return int|null Score obtido, ou null em caso de qualquer falha de integração.
     */
    private function consultarBureau(string $cpf): ?int
    {
        try {
            $response = Http::timeout(config('services.score_bureau.timeout'))
                ->get(config('services.score_bureau.url') . "/{$cpf}");
        } catch (ConnectionException $e) {
            Log::warning('Falha de conexão ao consultar o Bureau de Crédito.', [
                'cpf' => $cpf,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->failed() || ! is_numeric($response->json('score'))) {
            Log::warning('Resposta inválida do Bureau de Crédito.', [
                'cpf' => $cpf,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return (int) $response->json('score');
    }

    /**
     * Aplica as regras de elegibilidade de crédito na ordem definida pelo negócio.
     *
     * @return array<string, mixed>
     */
    private function aplicarRegras(float $rendaMensal, int $score, float $valorSolicitado): array
    {
        if ($rendaMensal < self::RENDA_MINIMA) {
            return [
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Renda mínima insuficiente',
            ];
        }

        if ($score < self::SCORE_MINIMO) {
            return [
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Score de crédito muito baixo',
            ];
        }

        $taxaJuros = $score >= self::SCORE_TAXA_REDUZIDA ? self::TAXA_SCORE_ALTO : self::TAXA_SCORE_MEDIO;

        $jurosTotais = $valorSolicitado * ($taxaJuros / 100) * self::NUMERO_PARCELAS;
        $valorTotal = $valorSolicitado + $jurosTotais;
        $valorParcela = $valorTotal / self::NUMERO_PARCELAS;

        if ($valorParcela > $rendaMensal * self::COMPROMETIMENTO_MAXIMO) {
            return [
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Comprometimento de renda superior a 30%',
            ];
        }

        return [
            'status' => StatusAnalise::APROVADO,
            'taxa_juros' => $taxaJuros,
            'valor_parcela' => round($valorParcela, 2),
        ];
    }
}
