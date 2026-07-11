<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;
use Laravel\Sanctum\Sanctum;
use App\Models\Funcionario;

class ClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $funcionario = Funcionario::create([
            'nome' => 'Administrador',
            'cargo' => 'Gerente',
            'usr' => 'admin',
            'password' => bcrypt('123')
        ]);

        Sanctum::actingAs($funcionario);
    }

    /** @test */
    public function test_deve_cadastrar_um_cliente_com_sucesso()
    {
        $dados = [
            'nome'     => 'Gustavo Pirolo',
            'email'    => 'gustavo@exemplo.com',
            'cpf'      => '12345678901',
            'cep'      => '06000000',
            'endereco' => 'Rua de Osasco',
            'estado'   => 'SP',
            'cidade'   => 'Osasco',
            'telefone' => '11999999999',
        ];

        $response = $this->postJson('/api/clientes', $dados);

        $response->assertStatus(201);

        $response->assertJsonPath('message', 'Cliente cadastrado com sucesso no Microserviço!');

        $this->assertDatabaseHas('clientes', ['email' => 'gustavo@exemplo.com']);
    }

    /** @test */
    public function test_nao_deve_cadastrar_cliente_com_cpf_faltando()
    {
        $dados = ['nome' => 'Incompleto'];

        $response = $this->postJson('/api/clientes', $dados);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['cpf']]);
    }

    /** @test */
    public function test_deve_retornar_um_cliente_especifico_por_id()
    {
        $cliente = Cliente::create([
            'nome' => 'Gustavo Pirolo',
            'email' => 'gustavo@teste.com',
            'cpf' => '11122233344',
            'cep' => '06000000',
            'endereco' => 'Osasco',
            'estado' => 'SP',
            'cidade' => 'Osasco',
            'telefone' => '1199999999'
        ]);

        $response = $this->getJson("/api/clientes/buscarPorId/{$cliente->clientid}");

        $response->assertStatus(200);
        $response->assertJsonPath('nome', 'Gustavo Pirolo');
    }

    /** @test */
    public function test_deve_retornar_404_se_cliente_nao_existir()
    {
        $response = $this->getJson("/api/clientes/buscarPorId/9999");
        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Cliente não encontrado!');
    }

    /** @test */
    public function test_deve_buscar_clientes_pelo_nome()
    {
        Cliente::create(['nome' => 'Marcos Silva', 'email' => 'm@a.com', 'cpf' => '1', 'cep' => '1', 'endereco' => '1', 'estado' => 'SP', 'cidade' => '1', 'telefone' => '1']);
        Cliente::create(['nome' => 'Maria Oliveira', 'email' => 'm@b.com', 'cpf' => '2', 'cep' => '1', 'endereco' => '1', 'estado' => 'SP', 'cidade' => '1', 'telefone' => '1']);

        $response = $this->getJson("/api/clientes/buscar?q=Maria");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nome' => 'Maria Oliveira']);
    }

    /** @test */
    public function test_deve_atualizar_dados_do_cliente_com_sucesso()
    {
        $cliente = Cliente::create(['nome' => 'Original', 'email' => 'o@o.com', 'cpf' => '11111111111', 'cep' => '1', 'endereco' => '1', 'estado' => 'SP', 'cidade' => '1', 'telefone' => '1']);

        $dadosNovos = [
            'nome' => 'Nome Atualizado',
            'cpf' => '222.222.222-22'
        ];

        $response = $this->putJson("/api/clientes/atualizarCliente/{$cliente->clientid}", $dadosNovos);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clientes', [
            'clientid' => $cliente->clientid,
            'nome' => 'Nome Atualizado',
            'cpf' => '22222222222'
        ]);
    }

    /** @test */
    public function test_deve_deletar_um_cliente_com_sucesso()
    {
        $funcionario = \App\Models\Funcionario::create([
            'nome' => 'Administrador',
            'cargo' => 'Gerente',
            'usr' => 'admin',
            'password' => bcrypt('123')
        ]);

        Sanctum::actingAs($funcionario);
        $cliente = Cliente::create(['nome' => 'Para Deletar', 'email' => 'd@d.com', 'cpf' => '3', 'cep' => '1', 'endereco' => '1', 'estado' => 'SP', 'cidade' => '1', 'telefone' => '1']);

        $response = $this->postJson("/api/clientes/deletarCliente/{$cliente->clientid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', "Cliente id {$cliente->clientid} foi deletado com sucesso!");

        $this->assertDatabaseMissing('clientes', ['clientid' => $cliente->clientid]);
    }
}
