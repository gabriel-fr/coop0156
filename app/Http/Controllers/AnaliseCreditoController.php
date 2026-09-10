<?php

namespace App\Http\Controllers;

use App\Enums\StatusAnalise;
use App\Http\Requests\SolicitarAnaliseCreditoRequest;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use App\Services\AnaliseCreditoService;

class AnaliseCreditoController extends Controller
{
    /**
     * Solicita uma nova análise de crédito.
     *
     * POST /api/analise-credito
     *
     * Campos esperados no body (JSON):
     *  - nome: string, obrigatório
     *  - cpf: string, obrigatório (11 dígitos)
     *  - renda_mensal: numeric, obrigatório
     *  - tipo_credito: string, obrigatório (pessoal | imobiliario | automotivo)
     *  - valor_solicitado: numeric, obrigatório
     *
     * @param  \App\Http\Requests\SolicitarAnaliseCreditoRequest  $request
     * @param  \App\Services\AnaliseCreditoService  $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitar(SolicitarAnaliseCreditoRequest $request, AnaliseCreditoService $service)
    {
        $analise = $service->solicitar($request->validated());

        return response()->json($analise, 201);
    }

    /**
     * Confirma a contratação de uma análise de crédito aprovada.
     *
     * POST /api/analise-credito/{id}/contratar
     *
     * Fluxo esperado:
     *  1. Buscar a análise pelo ID (retornar 404 se não encontrada).
     *  2. Verificar se o status é 'aprovado' (retornar 422 se não for).
     *  3. Atualizar o status para 'processando_contratacao' e disparar o
     *     ProcessarContratacaoJob, que finaliza a contratação de forma assíncrona.
     *  4. Retornar confirmação de sucesso.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function contratar($id)
    {
        $analise = AnaliseCredito::findOrFail($id);

        if ($analise->status !== StatusAnalise::APROVADO) {
            return response()->json([
                'message' => 'Apenas análises aprovadas podem ser contratadas.',
            ], 422);
        }

        $analise->update(['status' => StatusAnalise::PROCESSANDO_CONTRATACAO]);

        ProcessarContratacaoJob::dispatch($analise->id);

        return response()->json($analise);
    }
}
