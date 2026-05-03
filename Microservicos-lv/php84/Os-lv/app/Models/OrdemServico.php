<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServico extends Model
{
    protected $table = 'os';
    protected $primaryKey = 'osid';

    protected $fillable = [
        'clientid', 'carid', 'funcid', 'sintomas', 'valor_total', 'status_atual', 'statid_atual', 'entregue'
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
