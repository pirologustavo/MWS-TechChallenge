<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/** @mixin Builder */
class Funcionario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'funcionarios';

    protected $primaryKey = 'funcid';

    protected $fillable = [
        'nome',
        'cargo',
        'usr',
        'password',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
