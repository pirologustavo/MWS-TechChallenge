<?php

namespace App\Saga;

use App\Models\OrdemServico;
use App\Saga\Infrastructure\Outbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SagaCoordinator
{
    public const PENDING = 'PENDENTE';
    public const COMPLETED = 'CONCLUIDA';
    public const COMPENSATED = 'COMPENSADA';

    public function snapshot(OrdemServico $os): array
    {
        return [
            'os' => $os->getAttributes(),
            'items' => $os->itens()->get()->map(fn ($item) => $item->getAttributes())->all(),
        ];
    }

    /** Must be called inside the OS database transaction that saves the business change. */
    public function begin(
        int $osId,
        string $operation,
        array $items,
        ?array $beforeState,
        string $correlationId,
    ): string {
        $active = DB::table('saga_operations')->where('osid', $osId)->where('state', 'PENDING')->exists();
        if ($active) abort(409, 'Já existe uma Saga pendente para esta OS.');

        $sagaId = (string) Str::uuid();
        $eventId = (string) Str::uuid();
        $payload = [
            'saga_id' => $sagaId,
            'operation_id' => $sagaId,
            'osid' => $osId,
            'action' => $operation,
            'items' => array_values($items),
        ];

        DB::table('saga_operations')->insert([
            'id' => $sagaId,
            'osid' => $osId,
            'operation' => $operation,
            'state' => 'PENDING',
            'request_event_id' => $eventId,
            'before_state' => $beforeState === null ? null : json_encode($beforeState, JSON_THROW_ON_ERROR),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('os')->where('osid', $osId)->update(['saga_status' => self::PENDING, 'updated_at' => now()]);

        app(Outbox::class)->record(
            'OSStockAdjustmentRequested', 'os.stock.adjustment.requested', 'OrdemServico', $osId,
            $payload, $correlationId, $eventId
        );

        return $sagaId;
    }
}
