<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServicoItem extends Model
{
    protected $table = 'os_itens';
    protected $primaryKey = 'itemid';

    protected $fillable = [
        'osid', 'estoqid', 'quantidade', 'preco_unitario'
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class, 'osid', 'osid');
    }
}
