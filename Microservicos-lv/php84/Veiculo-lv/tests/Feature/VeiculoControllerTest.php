<?php

namespace Tests\Feature;

use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\Funcionario;

class VeiculoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('CREATE TABLE clientes (clientid INTEGER PRIMARY KEY, nome TEXT)');
        DB::table('clientes')->insert(['clientid' => 1, 'nome' => 'Gustavo Pirolo']);

        $funcionario = Funcionario::create([
            'nome' => 'Administrador',
            'cargo' => 'Gerente',
            'usr' => 'admin',
            'password' => bcrypt('123')
        ]);

        Sanctum::actingAs($funcionario);
    }

    /** @test */
    public function test_deve_cadastrar_um_veiculo_com_sucesso()
    {
        $dados = [
            'modelo'   => 'Golf GTI',
            'marca'    => 'Volkswagen',
            'placa'    => 'ABC1D23',
            'ano'      => 2024,
            'clientid' => 1
        ];

        $response = $this->postJson('/api/veiculos/salvar', $dados);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Veículo cadastrado com sucesso!');
        $this->assertDatabaseHas('veiculos', ['placa' => 'ABC1D23']);
    }

    /** @test */
    public function test_nao_deve_cadastrar_veiculo_com_placa_invalida()
    {
        $dados = [
            'modelo' => 'Fusca', 'marca' => 'VW', 'placa' => '123-AAAA', 'ano' => 1970, 'clientid' => 1
        ];

        $response = $this->postJson('/api/veiculos/salvar', $dados);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['placa']]);
    }

    /** @test */
    public function test_deve_listar_veiculos_com_nome_do_cliente()
    {
        Veiculo::create(['modelo' => 'Polo', 'marca' => 'VW', 'placa' => 'POL0A22', 'ano' => 2022, 'clientid' => 1]);

        $response = $this->getJson('/api/veiculos/listar');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nome' => 'Gustavo Pirolo', 'modelo' => 'Polo']);
    }

    /** @test */
    public function test_deve_buscar_veiculo_por_id()
    {
        $veiculo = Veiculo::create(['modelo' => 'Civic', 'marca' => 'Honda', 'placa' => 'HON0D24', 'ano' => 2024, 'clientid' => 1]);

        $response = $this->getJson("/api/veiculos/buscarPorId/{$veiculo->carid}");

        $response->assertStatus(200);
        $response->assertJsonPath('modelo', 'Civic');
    }

    /** @test */
    public function test_deve_buscar_todos_os_veiculos_de_um_cliente()
    {
        Veiculo::create(['modelo' => 'Carro 1', 'marca' => 'M1', 'placa' => 'AAA0A01', 'ano' => 2020, 'clientid' => 1]);
        Veiculo::create(['modelo' => 'Carro 2', 'marca' => 'M2', 'placa' => 'BBB0B02', 'ano' => 2021, 'clientid' => 1]);

        $response = $this->getJson("/api/veiculos/buscarVeiculoCliente/1");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /** @test */
    public function test_deve_atualizar_veiculo_com_sucesso()
    {
        $veiculo = Veiculo::create(['modelo' => 'Antigo', 'marca' => 'M', 'placa' => 'OLD0A00', 'ano' => 2000, 'clientid' => 1]);

        $response = $this->putJson("/api/veiculos/atualizarVeiculo/{$veiculo->carid}", [
            'modelo' => 'Novo Nome'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('veiculos', ['modelo' => 'Novo Nome']);
    }

    /** @test */
    public function test_deve_deletar_veiculo_com_sucesso()
    {
        $veiculo = Veiculo::create(['modelo' => 'Deletar', 'marca' => 'M', 'placa' => 'DEL0A00', 'ano' => 2000, 'clientid' => 1]);

        $response = $this->postJson("/api/veiculos/deletarVeiculo/{$veiculo->carid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('veiculos', ['carid' => $veiculo->carid]);
    }
}
