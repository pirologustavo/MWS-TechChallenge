<?php

namespace App\Jobs;

use App\Mail\OrcamentoAguardandoAprovacao;
use App\Models\OrdemServico;
use App\Saga\SagaCoordinator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendEstimate implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $backoff = 60;

    public function __construct(public int $osid) {}

    public function failed(?\Throwable $exception): void
    {
        DB::transaction(function (): void {
            $os = OrdemServico::where('osid', $this->osid)->lockForUpdate()->first();
            if (! $os || (int) $os->statid_atual !== 3 || $os->saga_status !== SagaCoordinator::COMPLETED) {
                return;
            }
            $before = app(SagaCoordinator::class)->snapshot($os);
            $items = $os->itens()->get()->map(fn ($item) => ['id' => (int) $item->estoqid, 'qtd' => -(int) $item->quantidade])->all();
            $os->update(['status_atual' => 'Cancelado', 'statid_atual' => 7]);
            $os->historicoStatus()->create(['statid' => 7]);
            if ($items) {
                app(SagaCoordinator::class)->begin($this->osid, 'cancel', $items, $before, (string) Str::uuid());
            }
        });
    }

    public function handle(): void
    {
        $os = OrdemServico::find($this->osid);
        if ($os && (int) $os->statid_atual === 3 && $os->saga_status === 'CONCLUIDA') {
            Mail::to($os->cliente_email_snapshot ?? 'cliente.teste@mwssandbox.com')->send(new OrcamentoAguardandoAprovacao($os));
        }
    }
}
