<?php

namespace Tests\Integration;

use App\Models\ClienteAuditEvent;
use App\Services\ClienteAuditTrail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClienteAuditMongoTest extends TestCase
{
    public function test_evento_de_auditoria_e_persistido_e_consultado_no_mongodb(): void
    {
        $clientId = random_int(100000, 999999);
        $correlationId = (string) Str::uuid();
        $audit = app(ClienteAuditTrail::class);

        $audit->record($clientId, 'updated', ['nome', 'telefone'], $correlationId);

        $event = $audit->history($clientId)->firstWhere('correlation_id', $correlationId);

        $this->assertNotNull($event, 'O documento precisa estar persistido na coleção MongoDB.');
        $this->assertSame('updated', $event->event);
        $this->assertSame(['nome', 'telefone'], $event->fields);

        ClienteAuditEvent::where('correlation_id', $correlationId)->delete();
    }
}
