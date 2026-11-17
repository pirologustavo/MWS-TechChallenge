<?php

namespace App\Console\Commands;

use App\Billing\BillingReconciler;
use Illuminate\Console\Command;

class ReconcileBilling extends Command
{
    protected $signature = 'billing:reconcile';

    protected $description = 'Reconcilia pagamentos e estorna cobranças de OS canceladas';

    public function handle(BillingReconciler $reconciler): int
    {
        return $reconciler->run() ? self::FAILURE : self::SUCCESS;
    }
}
