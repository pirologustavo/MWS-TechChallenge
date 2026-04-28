<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServico extends Model
{
    protected $table = 'os';
    protected $primaryKey = 'osid';

    protected $fillable = [
        'clientid', 'veiculoid', 'funcid', 'sintomas', 'valor_total', 'status_atual'
    ];

    public function itens()
    {
        return $this->hasMany(OrdemServicoItem::class, 'osid', 'osid');
    }

    public function historicoStatus()
    {
        return $this->hasMany(OrdemServicoStatus::class, 'osid', 'osid');
    }
}
