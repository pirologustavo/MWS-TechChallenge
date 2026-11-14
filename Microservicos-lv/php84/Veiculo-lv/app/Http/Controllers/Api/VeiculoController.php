<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Support\DogStatsD;

class VeiculoController extends Controller
{
    public function salvar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modelo'          => 'required|string|max:50',
            'marca'           => 'required|string|max:30',
            'placa'           => 'required|string|regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/|unique:veiculos',
            'ano'             => 'required|integer|min:1900',
            'clientid' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$this->clientes([(int) $request->clientid])) {
            return response()->json(['message' => 'Cliente informado não existe.'], 422);
        }

        $veiculo = Veiculo::create($request->all());

        return response()->json([
            'message' => 'Veículo cadastrado com sucesso!',
            'data'    => $veiculo
        ], 201);
    }

    public function listar()
    {
        $veiculos = Veiculo::all();
        $clientes = collect($this->clientes($veiculos->pluck('clientid')->unique()->values()->all()))->keyBy('clientid');
        return response()->json($veiculos->map(function ($veiculo) use ($clientes) {
            $veiculo->nome = $clientes[$veiculo->clientid]['nome'] ?? null;
            return $veiculo;
        }));
    }

    public function buscarPorId($id)
    {
        $veiculo = Veiculo::where('carid', $id)->first();

        if (!$veiculo) {
            return response()->json(['message' => "Veiculo não encontrado!"], 404);
        }

        $veiculo->nome = collect($this->clientes([(int) $veiculo->clientid]))->first()['nome'] ?? null;
        return response()->json($veiculo);
    }

    public function atualizarVeiculo(Request $request, $id)
    {
        $veiculo = Veiculo::where('carid', $id)->first();

        if (!$veiculo) {
            return response()->json(['message' => "Veiculo não encontrado"], 404);
        }

        $dados = $request->all();
        if (isset($dados['clientid']) && !$this->clientes([(int) $dados['clientid']])) {
            return response()->json(['message' => 'Cliente informado não existe.'], 422);
        }
        $veiculo->update($dados);

        return response()->json([
            'message' => 'Veiculo atualizado com sucesso!',
            'data'    => $veiculo
        ]);
    }

    public function buscarVeiculoCliente($id)
    {
        $veiculos = Veiculo::where('clientid', "{$id}")
            ->get(['carid', 'modelo', 'placa']);

        return response()->json($veiculos);
    }

    public function deletarVeiculo(string $id)
    {
        return DB::transaction(function () use ($id) {
            $veiculo = Veiculo::where('carid', $id)->first();

            if (!$veiculo) {
                return response()->json(['message' => 'Veiculo não encontrado'], 404);
            }

            $veiculo->delete();

            return response()->json(['message' => "Veiculo id $id foi deletado com sucesso!"]);
        });
    }

    private function clientes(array $ids): array
    {
        if (!$ids) return [];
        $baseUrl = (string) config('services.internal.cliente');
        if ($baseUrl === '') {
            DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'veiculo-lv', 'dependency' => 'cliente']);
            throw new HttpException(503, 'Serviço de clientes não configurado.');
        }
        $correlationId = request()->attributes->get('correlation_id')
            ?? request()->header('X-Correlation-ID')
            ?? (string) \Illuminate\Support\Str::uuid();
        request()->attributes->set('correlation_id', $correlationId);
        try {
            $response = Http::acceptJson()->withHeaders([
                'X-Internal-Token' => (string) config('services.internal_token'),
                'X-Correlation-ID' => $correlationId,
            ])->connectTimeout(2)->timeout(5)->get(rtrim($baseUrl, '/') . '/api/internal/v1/clientes', ['ids' => $ids]);
            if (!$response->successful()) {
                DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'veiculo-lv', 'dependency' => 'cliente']);
                throw new HttpException(503, 'Serviço de clientes indisponível.');
            }
            return $response->json() ?? [];
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'veiculo-lv', 'dependency' => 'cliente']);
            Log::warning('Falha ao consultar serviço de clientes', ['correlation_id' => $correlationId]);
            throw new HttpException(503, 'Serviço de clientes indisponível.');
        }
    }
}
