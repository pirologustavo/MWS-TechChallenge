<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @mixin Builder */
class Veiculo extends Model
{
    protected $table = 'veiculos';

    protected $primaryKey = 'carid';

    protected $fillable = ['modelo', 'marca', 'placa', 'ano', 'clientid'];
}
