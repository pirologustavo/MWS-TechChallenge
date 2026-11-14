<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\DogStatsD;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicoInterno
{
    public function ajustarEstoque(array $itens, string $idempotencyKey): array
    {
        $baseUrl = (string) config('services.internal.estoque');
        if ($baseUrl === '') {
            DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => 'estoque']);
            throw new HttpException(503, 'Serviço de estoque não configurado.');
        }
        $request = request();
        $correlationId = $request->attributes->get('correlation_id')
            ?? $request->header('X-Correlation-ID')
            ?? (string) \Illuminate\Support\Str::uuid();
        $request->attributes->set('correlation_id', $correlationId);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $response = Http::acceptJson()->withHeaders([
                    'X-Internal-Token' => (string) config('services.internal_token'),
                    'X-Correlation-ID' => $correlationId,
                    'Idempotency-Key' => $idempotencyKey,
                ])->connectTimeout(2)->timeout(5)->post(
                    rtrim($baseUrl, '/') . '/api/internal/v1/stock-adjustments',
                    ['items' => $itens]
                );
                if ($response->serverError() && $attempt === 0) continue;
                if (!$response->successful()) {
                    DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => 'estoque']);
                    Log::warning('Falha em ajuste de estoque entre serviços', [
                        'status' => $response->status(), 'correlation_id' => $correlationId,
                    ]);
                    throw new HttpException($response->status() >= 400 && $response->status() < 500 ? $response->status() : 503,
                        'Não foi possível ajustar o estoque.');
                }
                return $response->json() ?? [];
            } catch (ConnectionException $exception) {
                if ($attempt === 0) continue;
                DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => 'estoque']);
                Log::warning('Serviço de estoque inacessível durante ajuste', ['correlation_id' => $correlationId]);
                throw new HttpException(503, 'Serviço de estoque indisponível.');
            } catch (HttpException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => 'estoque']);
                Log::warning('Falha inesperada em ajuste de estoque', ['correlation_id' => $correlationId]);
                throw new HttpException(503, 'Não foi possível ajustar o estoque.');
            }
        }
        throw new HttpException(503, 'Serviço de estoque indisponível.');
    }

    public function consultar(string $servico, string $caminho, array $parametros = []): array
    {
        if (isset($parametros['ids']) && is_array($parametros['ids']) && count($parametros['ids']) > 200) {
            $resultados = [];
            foreach (array_chunk($parametros['ids'], 200) as $lote) {
                $loteParametros = $parametros;
                $loteParametros['ids'] = $lote;
                $resultados = array_merge($resultados, $this->consultar($servico, $caminho, $loteParametros));
            }
            return $resultados;
        }

        $baseUrl = (string) config("services.internal.{$servico}");
        if ($baseUrl === '') {
            DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => $servico]);
            throw new HttpException(503, 'Serviço dependente não configurado.');
        }
        $url = rtrim($baseUrl, '/') . $caminho;
        $currentRequest = request();
        $correlationId = $currentRequest->attributes->get('correlation_id')
            ?? $currentRequest->header('X-Correlation-ID')
            ?? (string) \Illuminate\Support\Str::uuid();
        $currentRequest->attributes->set('correlation_id', $correlationId);

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Internal-Token' => (string) config('services.internal_token'),
                    'X-Correlation-ID' => $correlationId,
                ])
                ->connectTimeout(2)
                ->timeout(5)
                ->get($url, $parametros);

            if (!$response->successful()) {
                DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => $servico]);
                Log::warning('Falha em chamada entre serviços', [
                    'service' => $servico,
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                ]);
                throw new HttpException(503, 'Serviço dependente indisponível.');
            }

            return $response->json() ?? [];
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            DogStatsD::metric('mws.integration.errors', 1, 'c', ['service' => 'os-lv', 'dependency' => $servico]);
            Log::warning('Serviço interno inacessível', [
                'service' => $servico,
                'correlation_id' => $correlationId,
                'exception' => $exception::class,
            ]);
            throw new HttpException(503, 'Serviço dependente indisponível.');
        }
    }
}
