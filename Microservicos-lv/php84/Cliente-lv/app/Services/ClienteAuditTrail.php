<?php

namespace App\Services;

use App\Models\ClienteAuditEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClienteAuditTrail
{
    /** @param list<string> $fields */
    public function record(int $clientId, string $event, array $fields, ?string $correlationId = null): void
    {
        try {
            ClienteAuditEvent::create([
                'client_id' => $clientId,
                'event' => $event,
                'fields' => array_values(array_unique($fields)),
                'correlation_id' => $correlationId,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // MySQL remains authoritative; MongoDB audit outages are observable and do not undo a committed customer write.
            Log::error('Falha ao persistir evento de auditoria do cliente no MongoDB', [
                'client_id' => $clientId,
                'event' => $event,
                'correlation_id' => $correlationId,
                'exception' => $exception::class,
            ]);
        }
    }

    /** @return Collection<int, ClienteAuditEvent> */
    public function history(int $clientId): Collection
    {
        return ClienteAuditEvent::query()
            ->where('client_id', $clientId)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();
    }
}
