<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    protected $table = 'funcionarios';
    protected $primaryKey = 'funcid';

    protected $fillable = [
        'nome',
        'cargo',
        'usr',
        'password'
    ];

    protected $hidden = [
        'password',
    ];
}
