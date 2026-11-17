<?php

namespace App\Http\Controllers;

use App\Billing\WebhookSignature;
use App\Models\OrdemServico;
use App\Saga\Infrastructure\Outbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private function client()
    {
        $token = config('mercadopago.access_token');
        abort_unless(is_string($token) && $token !== '', 503, 'Mercado Pago não configurado.');

        return Http::baseUrl((string) config('mercadopago.base_url', 'https://api.mercadopago.com'))->withToken($token)->acceptJson()->connectTimeout(3)->timeout(15);
    }

    public function webhook(Request $request)
    {
        // PHP normalizes query parameter dots to underscores.
        $id = (string) $request->query('data_id', $request->query('data.id', ''));
        abort_unless(app(WebhookSignature::class)->valid((string) $request->header('x-signature'), (string) $request->header('x-request-id'), $id, (string) config('mercadopago.webhook_secret')), 401);
        $payment = $this->client()->get('/v1/payments/'.$id)->throw()->json();
        $row = DB::table('billing_checkouts')->where('id', $payment['external_reference'] ?? '')->first();
        if (! $row) {
            return response()->json(['received' => true]);
        }

        return $this->verify(Request::create('/', 'POST', ['payment_id' => $id]), (int) $row->osid);
    }

    public function checkout(int $osid)
    {
        $client = $this->client();
        $row = DB::transaction(function () use ($osid) {
            $os = OrdemServico::where('osid', $osid)->lockForUpdate()->firstOrFail();
            abort_unless(in_array((int) $os->statid_atual, [4, 5], true) && $os->saga_status === 'CONCLUIDA', 409, 'A OS precisa estar aprovada e sem Saga pendente.');
            abort_unless((float) $os->valor_total > 0, 422, 'Orçamento sem valor para cobrança.');
            $existing = DB::table('billing_checkouts')->where('osid', $osid)->first();
            if ($existing) {
                abort_unless($existing->checkout_url, 409, 'Criação de checkout pendente de reconciliação.');

                return $existing;
            }
            $id = (string) Str::uuid();
            DB::table('billing_checkouts')->insert(['id' => $id, 'osid' => $osid, 'amount' => $os->valor_total, 'created_at' => now(), 'updated_at' => now()]);

            return DB::table('billing_checkouts')->where('id', $id)->first();
        });
        if ($row->checkout_url) {
            return response()->json($row);
        }
        $response = $client->post('/checkout/preferences', [
            'external_reference' => $row->id,
            'items' => [['id' => (string) $osid, 'title' => 'Ordem de serviço '.$osid, 'quantity' => 1, 'currency_id' => 'BRL', 'unit_price' => (float) $row->amount]],
        ])->throw()->json();
        abort_unless(isset($response['id'], $response['init_point']), 502, 'Resposta inválida do provedor.');
        DB::table('billing_checkouts')->where('id', $row->id)->update(['preference_id' => $response['id'], 'checkout_url' => $response['init_point'], 'updated_at' => now()]);

        return response()->json(DB::table('billing_checkouts')->where('id', $row->id)->first(), 201);
    }

    public function verify(Request $request, int $osid)
    {
        $data = $request->validate(['payment_id' => 'required|regex:/^[0-9]+$/']);
        $payment = $this->client()->get('/v1/payments/'.$data['payment_id'])->throw()->json();
        DB::transaction(function () use ($osid, $payment, $data): void {
            $os = OrdemServico::where('osid', $osid)->lockForUpdate()->first();
            $row = DB::table('billing_checkouts')->where('osid', $osid)->lockForUpdate()->first();
            abort_unless($row && ($payment['external_reference'] ?? '') === $row->id, 422, 'Pagamento não pertence a esta OS.');
            abort_unless((string) ($payment['id'] ?? '') === (string) $data['payment_id'] && ($payment['currency_id'] ?? '') === 'BRL' && (int) round(($payment['transaction_amount'] ?? 0) * 100) === (int) round($row->amount * 100), 422, 'Pagamento com valor ou moeda divergentes.');
            abort_if($row->status === 'approved' && $row->payment_id && $row->payment_id !== (string) $data['payment_id'], 409, 'Outro pagamento já vinculado.');
            $status = (string) ($payment['status'] ?? 'pending');
            if ($row->status === 'refunded' || ($row->status === $status && $row->payment_id === (string) $data['payment_id'])) {
                return;
            }
            DB::table('billing_checkouts')->where('id', $row->id)->update(['status' => $status, 'payment_id' => (string) $data['payment_id'], 'updated_at' => now()]);
            DB::table('os')->where('osid', $osid)->update(['payment_status' => $status, 'updated_at' => now()]);
            app(Outbox::class)->record('OSPaymentUpdated', 'os.payment.updated', 'OrdemServico', $osid, ['osid' => $osid, 'payment_status' => $status], request()->header('X-Correlation-ID'));
        });

        return response()->json(DB::table('billing_checkouts')->where('osid', $osid)->first());
    }

    public function refund(int $osid)
    {
        $row = DB::table('billing_checkouts')->where('osid', $osid)->first();
        abort_unless($row, 404);
        if ($row->status === 'refunded') {
            return response()->json($row);
        }
        abort_unless($row->status === 'approved' && $row->payment_id, 409, 'Pagamento não aprovado.');
        $refund = $this->client()->withHeaders(['X-Idempotency-Key' => $row->id])->withBody('{}', 'application/json')->post('/v1/payments/'.$row->payment_id.'/refunds')->throw()->json();
        abort_unless(($refund['status'] ?? '') === 'approved', 502, 'Estorno ainda não confirmado.');
        DB::transaction(function () use ($row, $osid): void {
            DB::table('billing_checkouts')->where('id', $row->id)->lockForUpdate()->first();
            $current = DB::table('billing_checkouts')->where('id', $row->id)->first();
            if ($current->status === 'refunded') {
                return;
            }
            DB::table('billing_checkouts')->where('id', $row->id)->update(['status' => 'refunded', 'updated_at' => now()]);
            DB::table('os')->where('osid', $osid)->update(['payment_status' => 'refunded', 'updated_at' => now()]);
            app(Outbox::class)->record('OSPaymentRefunded', 'os.payment.refunded', 'OrdemServico', $osid, ['osid' => $osid], request()->header('X-Correlation-ID'));
        });

        return response()->json(['status' => 'refunded']);
    }
}
