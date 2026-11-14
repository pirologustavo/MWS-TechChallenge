<?php

namespace App\Saga;

use App\Interface\OsStatusInterface;
use App\Mail\OrcamentoAguardandoAprovacao;
use App\Models\OrdemServico;
use App\Saga\Infrastructure\Outbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class StockAdjustmentResultHandler
{
    public function handle(array $event): void
    {
        foreach (['event_id', 'event_type', 'saga_id', 'operation_id', 'osid', 'request_event_id'] as $field) {
            if (empty($event[$field])) throw new InvalidArgumentException("Campo obrigatório ausente: {$field}");
        }
        if (!in_array($event['event_type'], ['stock.adjustment.succeeded', 'stock.adjustment.failed'], true)) {
            throw new InvalidArgumentException('Tipo de evento de estoque desconhecido.');
        }

        $sendApprovalEmailFor = null;
        DB::transaction(function () use ($event, &$sendApprovalEmailFor): void {
            if (DB::table('integration_inbox')->where('event_id', $event['event_id'])->exists()) return;

            $operation = DB::table('saga_operations')->where('id', $event['operation_id'])->lockForUpdate()->first();
            if (!$operation || $operation->request_event_id !== $event['request_event_id']) {
                throw new InvalidArgumentException('Resultado sem operação de Saga correspondente.');
            }

            if ($operation->state === 'PENDING') {
                if ($event['event_type'] === 'stock.adjustment.succeeded') {
                    $this->complete($operation, $event);
                    if ($operation->operation === 'analysis') $sendApprovalEmailFor = (int) $operation->osid;
                } else {
                    $this->compensate($operation, $event);
                }
            }

            DB::table('integration_inbox')->insert([
                'event_id' => $event['event_id'], 'event_type' => $event['event_type'], 'processed_at' => now(),
            ]);
        });

        if ($sendApprovalEmailFor !== null) {
            $os = OrdemServico::find($sendApprovalEmailFor);
            if ($os) {
                try {
                    Mail::to($os->cliente_email_snapshot ?? 'cliente.teste@mwssandbox.com')
                        ->send(new OrcamentoAguardandoAprovacao($os));
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('Falha ao enviar orçamento após Saga de estoque', [
                        'osid' => $sendApprovalEmailFor, 'correlation_id' => $event['correlation_id'] ?? null,
                    ]);
                }
            }
        }
    }

    private function complete(object $operation, array $event): void
    {
        if ($operation->operation === 'delete') {
            DB::table('os_itens')->where('osid', $operation->osid)->delete();
            DB::table('os_status')->where('osid', $operation->osid)->delete();
            DB::table('os')->where('osid', $operation->osid)->delete();
        } else {
            DB::table('os')->where('osid', $operation->osid)->update([
                'saga_status' => SagaCoordinator::COMPLETED, 'updated_at' => now(),
            ]);
        }

        DB::table('saga_operations')->where('id', $operation->id)->update([
            'state' => 'COMPLETED', 'failure_reason' => null, 'updated_at' => now(),
        ]);
        app(Outbox::class)->record(
            'OSStockAdjustmentCompleted', 'os.saga.completed', 'OrdemServico', $operation->osid,
            ['saga_id' => $operation->id, 'operation' => $operation->operation, 'stock_event_id' => $event['event_id']],
            $operation->correlation_id
        );
    }

    private function compensate(object $operation, array $event): void
    {
        $reason = (string) ($event['reason'] ?? 'Ajuste de estoque rejeitado.');
        $before = $operation->before_state ? json_decode($operation->before_state, true, 512, JSON_THROW_ON_ERROR) : null;

        if ($operation->operation === 'create') {
            DB::table('os')->where('osid', $operation->osid)->update([
                'status_atual' => OsStatusInterface::CANCELADO[0],
                'statid_atual' => OsStatusInterface::CANCELADO[1],
                'saga_status' => SagaCoordinator::COMPENSATED,
                'updated_at' => now(),
            ]);
            DB::table('os_status')->insert([
                'osid' => $operation->osid, 'statid' => OsStatusInterface::CANCELADO[1],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } elseif ($before !== null) {
            $attributes = $before['os'] ?? [];
            unset($attributes['osid'], $attributes['created_at'], $attributes['updated_at']);
            $attributes['saga_status'] = SagaCoordinator::COMPLETED;
            $attributes['updated_at'] = now();
            DB::table('os')->where('osid', $operation->osid)->update($attributes);

            DB::table('os_itens')->where('osid', $operation->osid)->delete();
            foreach ($before['items'] ?? [] as $item) {
                unset($item['id']);
                DB::table('os_itens')->insert($item);
            }
        }

        DB::table('saga_operations')->where('id', $operation->id)->update([
            'state' => 'COMPENSATED', 'failure_reason' => mb_substr($reason, 0, 4000), 'updated_at' => now(),
        ]);
        app(Outbox::class)->record(
            'OSSagaCompensated', 'os.saga.compensated', 'OrdemServico', $operation->osid,
            ['saga_id' => $operation->id, 'operation' => $operation->operation, 'reason' => $reason],
            $operation->correlation_id
        );
    }
}
