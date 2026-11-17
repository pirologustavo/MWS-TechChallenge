<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @mixin Builder */
class Cliente extends Model
{
    protected $table = 'clientes';

    protected $primaryKey = 'clientid';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
    ];
}
