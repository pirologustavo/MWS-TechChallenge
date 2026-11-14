<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServicoItem extends Model
{
    protected $table = 'os_itens';
    protected $primaryKey = 'id';

    protected $fillable = [
        'osid',
        'estoqid',
        'quantidade',
        'valor_unitario',
        'subtotal',
        'descricao_snapshot'
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class, 'osid', 'osid');
    }
}
