<?php

namespace Database\Seeders;

use App\Models\FuncUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // FuncUser::factory(10)->create();

        FuncUser::factory()->create([
            'name' => 'Test FuncUser',
            'email' => 'test@example.com',
        ]);
    }
}
