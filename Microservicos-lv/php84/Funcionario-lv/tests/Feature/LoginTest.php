<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_failure_and_success(): void
    {
        $this->postJson('/api/login', [])->assertUnprocessable();
        $this->postJson('/api/login', ['usr' => 'none', 'password' => 'bad'])->assertStatus(411);
        Funcionario::create(['nome' => 'Mecânico', 'usr' => 'worker', 'password' => bcrypt('correct'), 'cargo' => 'Mecânico']);
        $this->postJson('/api/login', ['usr' => 'worker', 'password' => 'bad'])->assertStatus(411);
        $this->postJson('/api/login', ['usr' => 'worker', 'password' => 'correct'])->assertOk()->assertJsonStructure(['token', 'user']);
    }
}
