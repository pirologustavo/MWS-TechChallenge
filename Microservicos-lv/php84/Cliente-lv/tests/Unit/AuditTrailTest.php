<?php

namespace Tests\Unit;

use App\Services\ClienteAuditTrail;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    public function test_record_normalizes_fields_and_history_is_limited(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('create')->once()->with(Mockery::on(fn ($data) => $data['fields'] === ['nome'] && $data['correlation_id'] === 'corr'));
        $query->shouldReceive('where')->with('client_id', 1)->andReturnSelf();
        $query->shouldReceive('orderByDesc')->with('occurred_at')->andReturnSelf();
        $query->shouldReceive('limit')->with(100)->andReturnSelf();
        $query->shouldReceive('get')->andReturn(collect(['event']));
        $service = Mockery::mock(ClienteAuditTrail::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('query')->andReturn($query);
        $service->record(1, 'created', ['nome', 'nome'], 'corr');
        $this->assertSame(['event'], $service->history(1)->all());
    }

    public function test_mongo_failure_is_logged_without_undoing_customer(): void
    {
        $service = Mockery::mock(ClienteAuditTrail::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('query')->andThrow(new \RuntimeException('offline'));
        Log::shouldReceive('error')->once();
        $service->record(1, 'created', []);
    }
}
