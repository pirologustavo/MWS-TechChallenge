<?php

namespace App\Saga;

use App\Saga\Infrastructure\Outbox;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StockAdjustmentRequestedHandler
{
    public function __construct(private readonly StockAdjustmentService $adjustments, private readonly Outbox $outbox)
    {
    }

    public function handle(array $event): void
    {
        foreach (['event_id', 'event_type', 'saga_id', 'operation_id', 'osid', 'action', 'items'] as $field) {
            if (!array_key_exists($field, $event) || $event[$field] === null || $event[$field] === '') {
                throw new InvalidArgumentException("Campo obrigatório ausente: {$field}");
            }
        }
        if ($event['event_type'] !== 'OSStockAdjustmentRequested' || !is_array($event['items']) || !$event['items']) {
            throw new InvalidArgumentException('Evento de ajuste de estoque inválido.');
        }
        foreach ($event['items'] as $item) {
            if (!is_array($item) || (int) ($item['id'] ?? 0) < 1 || (int) ($item['qtd'] ?? 0) === 0) {
                throw new InvalidArgumentException('Item de ajuste inválido.');
            }
        }

        try {
            DB::transaction(function () use ($event): void {
                if (DB::table('integration_inbox')->where('event_id', $event['event_id'])->exists()) return;

                $this->adjustments->applyWithinTransaction($event['event_id'], $event['items']);
                DB::table('integration_inbox')->insert([
                    'event_id' => $event['event_id'], 'event_type' => $event['event_type'], 'processed_at' => now(),
                ]);
                $this->publishResult($event, true, null);
            });
        } catch (StockAdjustmentRejected $exception) {
            DB::transaction(function () use ($event, $exception): void {
                if (DB::table('integration_inbox')->where('event_id', $event['event_id'])->exists()) return;
                DB::table('integration_inbox')->insert([
                    'event_id' => $event['event_id'], 'event_type' => $event['event_type'], 'processed_at' => now(),
                ]);
                $this->publishResult($event, false, $exception->getMessage());
            });
        }
    }

    private function publishResult(array $request, bool $success, ?string $reason): void
    {
        $type = $success ? 'stock.adjustment.succeeded' : 'stock.adjustment.failed';
        $routingKey = $type;
        $payload = [
            'saga_id' => $request['saga_id'],
            'operation_id' => $request['operation_id'],
            'osid' => $request['osid'],
            'action' => $request['action'],
            'request_event_id' => $request['event_id'],
            'items' => $request['items'],
        ];
        if (!$success) $payload['reason'] = $reason;

        $this->outbox->record(
            $type, $routingKey, 'Estoque', $request['osid'], $payload,
            $request['correlation_id'] ?? null
        );
    }
}
