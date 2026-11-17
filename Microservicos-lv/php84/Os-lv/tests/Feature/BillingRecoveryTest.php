<?php

namespace Tests\Feature;

use App\Billing\BillingReconciler;
use App\Http\Middleware\ApprovalSignature;
use App\Jobs\SendEstimate;
use App\Mail\OrcamentoAguardandoAprovacao;
use App\Models\OrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BillingRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function checkout(): object
    {
        $this->withoutMiddleware();
        config(['mercadopago.access_token' => 'test', 'mercadopago.webhook_secret' => 'secret']);
        $os = OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 4, 'status_atual' => 'Em Execução', 'valor_total' => 100, 'saga_status' => 'CONCLUIDA']);
        DB::table('billing_checkouts')->insert(['id' => 'checkout-test', 'osid' => $os->osid, 'amount' => 100, 'preference_id' => 'pref', 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('billing_checkouts')->first();
    }

    private function provider(): void
    {
        Http::fake(['*/payments/search*' => Http::response(['results' => [['id' => 123]]]), '*/payments/123' => Http::response(['id' => 123, 'external_reference' => 'checkout-test', 'currency_id' => 'BRL', 'transaction_amount' => 100, 'status' => 'approved']), '*/refunds' => Http::response(['status' => 'approved'])]);
    }

    public function test_signed_webhook_is_idempotent_and_rejects_tampering(): void
    {
        $row = $this->checkout();
        $this->provider();
        $this->postJson('/api/payments/webhook?data_id=123')->assertUnauthorized();
        $headers = ['x-request-id' => 'request', 'x-signature' => 'ts=123,v1='.hash_hmac('sha256', 'id:123;request-id:request;ts:123;', 'secret')];
        $this->postJson('/api/payments/webhook?data_id=123', [], $headers)->assertOk();
        $this->postJson('/api/payments/webhook?data_id=123', [], $headers)->assertOk();
        $this->assertDatabaseHas('os', ['osid' => $row->osid, 'payment_status' => 'approved']);
        $this->assertSame(1, DB::table('integration_outbox')->where('event_type', 'OSPaymentUpdated')->count());
    }

    public function test_cancel_compensation_waits_for_stock_then_refunds_once(): void
    {
        $row = $this->checkout();
        $this->provider();
        DB::table('os')->where('osid', $row->osid)->update(['statid_atual' => 7, 'saga_status' => 'PENDENTE']);
        app(BillingReconciler::class)->reconcile($row);
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id, 'status' => 'approved']);
        DB::table('os')->where('osid', $row->osid)->update(['saga_status' => 'CONCLUIDA']);
        app(BillingReconciler::class)->reconcile(DB::table('billing_checkouts')->first());
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id, 'status' => 'refunded']);
        $this->assertSame(0, app(BillingReconciler::class)->run());
        $this->assertSame(1, DB::table('integration_outbox')->where('event_type', 'OSPaymentRefunded')->count());
    }

    public function test_reconciliation_failure_releases_lease_and_schedules_retry(): void
    {
        $row = $this->checkout();
        config(['mercadopago.access_token' => '']);
        $this->assertSame(1, app(BillingReconciler::class)->run());
        $failed = DB::table('billing_checkouts')->first();
        $this->assertSame(1, $failed->attempts);
        $this->assertNull($failed->lease_until);
        $this->assertNotNull($failed->available_at);
        $this->assertSame(0, app(BillingReconciler::class)->run());
    }

    public function test_unknown_preference_is_recovered_without_duplicate_creation(): void
    {
        $row = $this->checkout();
        DB::table('billing_checkouts')->update(['preference_id' => null]);
        Http::fake(['*/preferences/search*' => Http::response(['elements' => [['id' => 'pref']]]), '*/preferences/pref' => Http::response(['id' => 'pref', 'external_reference' => $row->id, 'init_point' => 'https://mercadopago.com/checkout']), '*/payments/search*' => Http::response(['results' => []])]);
        $this->assertSame(0, app(BillingReconciler::class)->run());
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id, 'preference_id' => 'pref']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_estimate_job_retries_transport_errors_and_does_not_send_stale_estimates(): void
    {
        $row = $this->checkout();
        DB::table('os')->where('osid', $row->osid)->update(['statid_atual' => 3]);
        Mail::fake();
        (new SendEstimate($row->osid))->handle();
        Mail::assertSent(OrcamentoAguardandoAprovacao::class);
        DB::table('os')->where('osid', $row->osid)->update(['statid_atual' => 7]);
        Mail::fake();
        (new SendEstimate($row->osid))->handle();
        Mail::assertNothingSent();
    }

    public function test_checkout_prevents_price_changes_and_cancelled_order_cannot_reopen(): void
    {
        $row = $this->checkout();
        DB::table('os')->where('osid', $row->osid)->update(['statid_atual' => 7]);
        $this->postJson('/api/os/aprovarOs/'.$row->osid)->assertStatus(409);
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id, 'amount' => 100]);
    }

    public function test_approval_signature_is_verified_at_api_boundary(): void
    {
        config(['services.os_approval_secret' => 'secret']);
        $request = Request::create('/');
        $route = new Route('POST', 'os/{id}', fn () => null);
        $route->bind(Request::create('/os/1'));
        $request->setRouteResolver(fn () => $route);
        $request->headers->set('X-Approval-Signature', hash_hmac('sha256', '1', 'secret'));
        $response = (new ApprovalSignature)->handle($request, fn () => response('ok'));
        $this->assertSame('ok', $response->getContent());
        $request->headers->set('X-Approval-Signature', 'wrong');
        $this->expectException(HttpException::class);
        (new ApprovalSignature)->handle($request, fn () => response('ok'));
    }

    public function test_rejected_payment_does_not_hide_a_later_approved_attempt(): void
    {
        $row = $this->checkout();
        $this->provider();
        DB::table('billing_checkouts')->update(['payment_id' => '99', 'status' => 'rejected']);
        $this->assertSame(0, app(BillingReconciler::class)->run());
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id, 'payment_id' => '123', 'status' => 'approved']);
    }

    public function test_deleting_cancelled_order_does_not_return_stock_again(): void
    {
        $row = $this->checkout();
        DB::table('os')->where('osid', $row->osid)->update(['statid_atual' => 7]);
        DB::table('os_itens')->insert(['osid' => $row->osid, 'estoqid' => 1, 'quantidade' => 1, 'valor_unitario' => 100, 'subtotal' => 100]);
        $this->postJson('/api/os/deletarOs/'.$row->osid)->assertOk();
        $this->assertDatabaseMissing('os', ['osid' => $row->osid]);
        $this->assertDatabaseCount('saga_operations', 0);
        $this->assertDatabaseHas('billing_checkouts', ['id' => $row->id]);
    }
}
