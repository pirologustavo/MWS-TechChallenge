<?php

namespace App\Saga;

use App\Saga\Infrastructure\Outbox;
use Illuminate\Support\Facades\DB;

final class SagaRecovery
{
    public function cancel(string $operationId): void
    {
        DB::transaction(function () use ($operationId): void {
            $operation = DB::table('saga_operations')->where('id', $operationId)->lockForUpdate()->first();
            if (! $operation || ! in_array($operation->state, ['PENDING', 'CANCELLING'], true)) {
                return;
            }
            $request = DB::table('integration_outbox')->where('id', $operation->request_event_id)->first();
            if (! $request) {
                throw new \RuntimeException('Solicitação original da Saga não encontrada.');
            }
            if ($operation->state === 'PENDING') {
                $payload = json_decode($request->payload, true, 512, JSON_THROW_ON_ERROR);
                $payload['request_event_id'] = $request->id;
                app(Outbox::class)->record('OSStockAdjustmentCancellationRequested', 'os.stock.adjustment.requested',
                    'SagaCancellation', $operationId, $payload, $operation->correlation_id);
            } else {
                DB::table('integration_outbox')->where('aggregate_type', 'SagaCancellation')
                    ->where('aggregate_id', $operationId)->update(['published_at' => null, 'available_at' => now()]);
            }
            DB::table('saga_operations')->where('id', $operationId)->update([
                'state' => 'CANCELLING', 'failure_reason' => 'Cancelamento aguardando confirmação do estoque.', 'updated_at' => now(),
            ]);
        });
    }
}
