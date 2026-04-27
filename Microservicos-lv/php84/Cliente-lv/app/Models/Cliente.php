<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'clientid';
    protected $fillable = [
        'nome', 'email', 'cpf', 'cep', 'endereco', 'cidade', 'estado', 'telefone'
    ];
}
