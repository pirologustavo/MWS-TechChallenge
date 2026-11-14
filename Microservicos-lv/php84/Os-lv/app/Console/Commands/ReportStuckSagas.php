<?php

namespace App\Console\Commands;

use App\Support\DogStatsD;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportStuckSagas extends Command
{
    protected $signature = 'saga:report-stuck {--minutes=5}';
    protected $description = 'Report OS Sagas that remain pending beyond the expected processing window';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $pending = DB::table('saga_operations')
            ->where('state', 'PENDING')
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->get(['id', 'osid', 'operation', 'correlation_id', 'updated_at']);

        DogStatsD::metric('mws.saga.stuck', $pending->count(), 'g', ['service' => 'os-lv']);
        foreach ($pending as $saga) {
            Log::critical('Saga de OS pendente além do limite', [
                'saga_id' => $saga->id,
                'osid' => $saga->osid,
                'operation' => $saga->operation,
                'correlation_id' => $saga->correlation_id,
                'pending_since' => $saga->updated_at,
            ]);
        }

        return self::SUCCESS;
    }
}
