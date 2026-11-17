<?php

namespace App\Billing;

use App\Http\Controllers\PaymentController;
use App\Support\DogStatsD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class BillingReconciler
{
    public function run(): int
    {
        $failures = 0;
        $rows = DB::table('billing_checkouts')->where('status', '!=', 'refunded')->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))->orderBy('updated_at')->limit(100)->get();
        foreach ($rows as $row) {
            $claimed = DB::table('billing_checkouts')->where('id', $row->id)->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<', now()))->update(['lease_until' => now()->addMinutes(5)]);
            if (! $claimed) {
                continue;
            }
            try {
                $this->reconcile($row);
                DB::table('billing_checkouts')->where('id', $row->id)->update(['attempts' => 0, 'last_error' => null, 'lease_until' => null, 'available_at' => now()->addMinute(), 'updated_at' => now()]);
            } catch (\Throwable $e) {
                $failures++;
                DB::table('billing_checkouts')->where('id', $row->id)->update(['attempts' => $row->attempts + 1, 'last_error' => mb_substr($e->getMessage(), 0, 1000), 'lease_until' => null, 'available_at' => now()->addSeconds(min(300, 2 ** min(8, $row->attempts + 1))), 'updated_at' => now()]);
                DogStatsD::metric('mws.billing.reconciliation.failures', 1, 'c', ['service' => 'os-lv']);
                Log::error('billing.reconciliation.failed', ['osid' => $row->osid, 'checkout_id' => $row->id, 'exception' => $e::class]);
            }
        }

        return $failures;
    }

    public function reconcile(object $row): void
    {
        $token = config('mercadopago.access_token');
        if (! $token) {
            throw new \RuntimeException('Mercado Pago não configurado.');
        }
        $client = Http::baseUrl((string) config('mercadopago.base_url', 'https://api.mercadopago.com'))->withToken($token)->acceptJson()->connectTimeout(3)->timeout(15);
        if (! $row->preference_id) {
            $found = $client->get('/checkout/preferences/search', ['external_reference' => $row->id])->throw()->json('elements', []);
            if (! $found) {
                throw new \RuntimeException('Preferência incerta: aguardando reconciliação, sem criar cobrança duplicada.');
            }
            $preference = $client->get('/checkout/preferences/'.$found[0]['id'])->throw()->json();
            if (($preference['external_reference'] ?? '') !== $row->id || empty($preference['init_point'])) {
                throw new \RuntimeException('Preferência divergente.');
            }
            DB::table('billing_checkouts')->where('id', $row->id)->update(['preference_id' => $preference['id'], 'checkout_url' => $preference['init_point']]);
        }
        $payments = ($row->payment_id && $row->status === 'approved') ? [['id' => $row->payment_id]] : $client->get('/v1/payments/search', ['external_reference' => $row->id, 'sort' => 'date_created', 'criteria' => 'desc'])->throw()->json('results', []);
        $controller = app(PaymentController::class);
        foreach ($payments as $payment) {
            $controller->verify(Request::create('/', 'POST', ['payment_id' => (string) $payment['id']]), (int) $row->osid);
            if (DB::table('billing_checkouts')->where('id', $row->id)->value('status') === 'approved') {
                break;
            }
        }
        $current = DB::table('billing_checkouts')->where('id', $row->id)->first();
        $os = DB::table('os')->where('osid', $row->osid)->first();
        if ((! $os || ((int) $os->statid_atual === 7 && $os->saga_status === 'CONCLUIDA')) && $current->status === 'approved') {
            $controller->refund((int) $row->osid);
        }
    }
}
