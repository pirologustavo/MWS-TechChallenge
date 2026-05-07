<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FuncionarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('funcionarios')->insert([
            'nome' => 'Administrador Padrão',
            'cargo' => 'Gerente',
            'usr' => 'admin',
            'password' => Hash::make('admin123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('funcionarios')->insert([
            'nome' => 'José',
            'cargo' => 'Mecânico',
            'usr' => 'tecnico',
            'password' => Hash::make('123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
