<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServicoStatus extends Model
{
    protected $table = 'os_status';
    protected $primaryKey = 'osstatid';

    protected $fillable = [
        'osid', 'statid'
    ];

    // Relacionamento: Este registro de histórico aponta para um status do domínio
    public function statusFixo()
    {
        return $this->belongsTo(Status::class, 'statid', 'statid');
    }
}
