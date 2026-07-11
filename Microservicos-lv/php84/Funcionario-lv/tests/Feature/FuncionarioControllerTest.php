<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class FuncionarioControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_deve_listar_todos_os_funcionarios_com_campos_especificos()
    {
        $funcionario = Funcionario::create([
            'nome' => 'Gustavo Pirolo',
            'cargo' => 'Desenvolvedor',
            'usr' => 'gustavo.pirolo',
            'password' => bcrypt('123')
        ]);

        Sanctum::actingAs($funcionario);

        $funcionario = Funcionario::create([
            'nome' => 'Erikson',
            'cargo' => 'Gerente',
            'usr' => 'erikson',
            'password' => bcrypt('123')
        ]);

        Sanctum::actingAs($funcionario);

        $response = $this->getJson('/api/funcionario');

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $response->assertJsonStructure([
            '*' => ['funcid', 'nome', 'cargo']
        ]);
    }

    /** @test */
    public function test_deve_buscar_apenas_funcionarios_com_cargo_mecanico()
    {
        $funcionario = Funcionario::create([
            'nome' => 'Claudio Mecanico',
            'cargo' => 'Mecânico',
            'usr' => 'claudio.m',
            'password' => 'secret'
        ]);

        Sanctum::actingAs($funcionario);

        $funcionario = Funcionario::create([
            'nome' => 'Claudio Atendente',
            'cargo' => 'Atendente',
            'usr' => 'claudio.a',
            'password' => 'secret'
        ]);

        Sanctum::actingAs($funcionario);

        $response = $this->getJson('/api/funcionario/buscarMecanico?q=Claudio');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nome' => 'Claudio Mecanico']);
    }

    /** @test */
    public function test_deve_retornar_lista_vazia_se_mecanico_nao_for_encontrado()
    {
        $funcionario = Funcionario::create([
            'nome' => 'Joao',
            'cargo' => 'Mecânico',
            'usr' => 'joao.m',
            'password' => 'secret'
        ]);

        Sanctum::actingAs($funcionario);

        $response = $this->getJson('/api/funcionario/buscarMecanico?q=Inexistente');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }
}
