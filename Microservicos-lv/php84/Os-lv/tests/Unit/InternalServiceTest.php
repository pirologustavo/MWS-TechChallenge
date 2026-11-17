<?php

namespace Tests\Unit;

use App\Http\Service\ServicoInterno;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InternalServiceTest extends TestCase
{
    public function test_batches_preserve_results_and_stock_retry_preserves_idempotency(): void
    {
        config(['services.internal.estoque' => 'http://stock.test', 'services.internal_token' => 'internal']);
        Http::fake(['*/items*' => Http::sequence()->push([['id' => 1]])->push([['id' => 201]]), '*/stock-adjustments' => Http::sequence()->push([], 500)->push(['ok' => true])]);
        $service = new ServicoInterno;
        $this->assertSame([['id' => 1], ['id' => 201]], $service->consultar('estoque', '/items', ['ids' => range(1, 201)]));
        $this->assertSame(['ok' => true], $service->ajustarEstoque([['id' => 1, 'qtd' => 1]], 'operation-1'));
        Http::assertSent(fn ($r) => $r->hasHeader('Idempotency-Key', 'operation-1'));
        Http::assertSentCount(4);
    }

    public function test_dependency_failures_are_reported_without_partial_success(): void
    {
        $service = new ServicoInterno;
        foreach (['missing', 'http', 'network', 'unexpected'] as $failure) {
            config(['services.internal.estoque' => $failure === 'missing' ? '' : 'http://stock.test']);
            Http::fake(fn () => match ($failure) {
                'network' => throw new ConnectionException('offline'),'unexpected' => throw new \RuntimeException('failure'),default => Http::response([], 500)
            });
            foreach ([fn () => $service->consultar('estoque', '/items'), fn () => $service->ajustarEstoque([], 'operation')] as $call) {
                try {
                    $call();
                    $this->fail('Expected dependency error');
                } catch (HttpException $e) {
                    $this->assertSame(503, $e->getStatusCode());
                }
            }
        }
    }
}
