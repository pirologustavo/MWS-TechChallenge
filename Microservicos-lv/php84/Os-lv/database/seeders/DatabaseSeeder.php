<?php

namespace Database\Seeders;

use App\Models\FuncUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::table('status')->insert([
            ['statusdesc' => 'Aberta'],
            ['statusdesc' => 'Em Análise'],
            ['statusdesc' => 'Aguardando Peças'],
            ['statusdesc' => 'Concluída'],
            ['statusdesc' => 'Cancelada'],
        ]);
    }
}
