<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Funcionario extends Model
{
    use HasApiTokens;
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
