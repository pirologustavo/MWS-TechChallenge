<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_verification_and_idempotent_refund(): void
    {
        $this->withoutMiddleware();
        config(['mercadopago.access_token' => 'test-token']);
        $os = OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 4, 'status_atual' => 'Em Execução', 'valor_total' => 100, 'saga_status' => 'CONCLUIDA']);
        Http::fake(['*/checkout/preferences' => Http::response(['id' => 'preference-1', 'init_point' => 'https://mercadopago.com/checkout']), '*/refunds' => Http::response(['status' => 'approved'])]);
        $checkout = $this->postJson('/api/os/'.$os->osid.'/checkout')->assertCreated()->json();
        Http::fake(['*/v1/payments/123' => Http::response(['id' => 123, 'external_reference' => $checkout['id'], 'currency_id' => 'BRL', 'transaction_amount' => 100, 'status' => 'approved']), '*/refunds' => Http::response(['status' => 'approved'])]);
        $this->postJson('/api/os/'.$os->osid.'/pagamento/verificar', ['payment_id' => '123'])->assertOk();
        $this->assertDatabaseHas('os', ['osid' => $os->osid, 'payment_status' => 'approved']);
        $this->postJson('/api/os/'.$os->osid.'/pagamento/estornar')->assertOk();
        $this->postJson('/api/os/'.$os->osid.'/pagamento/estornar')->assertOk();
        $this->assertDatabaseHas('os', ['osid' => $os->osid, 'payment_status' => 'refunded']);
        $this->assertSame(1, DB::table('integration_outbox')->where('event_type', 'OSPaymentRefunded')->count());
    }
}
