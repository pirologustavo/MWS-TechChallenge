<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdemServico extends Model
{
    protected $table = 'os';
    protected $primaryKey = 'osid';

    protected $fillable = [
        'clientid', 'carid', 'funcid', 'sintomas', 'valor_total', 'status_atual', 'statid_atual', 'entregue',
        'cliente_nome_snapshot', 'cliente_email_snapshot', 'veiculo_modelo_snapshot', 'veiculo_placa_snapshot', 'funcionario_nome_snapshot', 'analise', 'status_changed_at', 'saga_status'
    ];

    protected function casts(): array
    {
        return ['status_changed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $os): void {
            $os->status_changed_at ??= now();
        });

        static::updating(function (self $os): void {
            if (!$os->isDirty('status_atual')) return;

            $previousStatus = (string) $os->getOriginal('status_atual');
            $startedAt = $os->status_changed_at ?? Carbon::parse($os->getRawOriginal('updated_at') ?? $os->getRawOriginal('created_at'));
            $durationSeconds = max(0, now()->diffInSeconds($startedAt, true));
            $statusTag = match (mb_strtolower($previousStatus)) {
                'em diagnóstico' => 'diagnostico',
                'em execução' => 'execucao',
                'finalizado', 'finalizada' => 'finalizacao',
                default => 'outro',
            };
            if ($statusTag !== 'outro') {
                DB::afterCommit(static function () use ($durationSeconds, $statusTag): void {
                    \App\Support\DogStatsD::metric('mws.os.status.duration', $durationSeconds, 'h', [
                        'service' => 'os-lv', 'status' => $statusTag,
                    ]);
                });
            }
            $os->status_changed_at = now();
        });
    }

    public function itens()
    {
        return $this->hasMany(OrdemServicoItem::class, 'osid', 'osid');
    }

    public function historicoStatus()
    {
        return $this->hasMany(OrdemServicoStatus::class, 'osid', 'osid');
    }

}
