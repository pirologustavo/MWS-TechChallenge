<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrdemServicoControllerTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        DB::statement('DROP TABLE IF EXISTS os_itens');
        DB::statement('DROP TABLE IF EXISTS os');

        DB::statement('CREATE TABLE clientes (clientid INTEGER PRIMARY KEY, nome TEXT)');
        DB::statement('CREATE TABLE veiculos (carid INTEGER PRIMARY KEY, modelo TEXT, placa TEXT)');
        DB::statement('CREATE TABLE funcionarios (funcid INTEGER PRIMARY KEY, nome TEXT)');
        DB::statement('CREATE TABLE estoque (estoqid INTEGER PRIMARY KEY, descricao TEXT)');

        DB::statement('CREATE TABLE os (
            osid INTEGER PRIMARY KEY AUTOINCREMENT,
            clientid INTEGER,
            carid INTEGER,
            funcid INTEGER,
            sintomas TEXT,
            analise TEXT,
            status_atual TEXT,
            statid_atual INTEGER,
            valor_total DECIMAL(10,2),
            entregue INTEGER DEFAULT 0,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )');

        DB::statement('CREATE TABLE os_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            osid INTEGER,
            estoqid INTEGER,
            quantidade INTEGER,
            valor_unitario DECIMAL(10,2),
            subtotal DECIMAL(10,2),
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )');

        DB::table('clientes')->insert(['clientid' => 1, 'nome' => 'Gustavo Pirolo']);
        DB::table('veiculos')->insert(['carid' => 1, 'modelo' => 'Golf GTI', 'placa' => 'ABC-1234']);
        DB::table('funcionarios')->insert(['funcid' => 1, 'nome' => 'Mecânico Claudio']);
        DB::table('estoque')->insert(['estoqid' => 1, 'descricao' => 'Amortecedor']);
    }

    /** @test */
    public function test_deve_criar_uma_os_com_itens_e_historico()
    {
        $dados = [
            'clientid' => 1,
            'carid'    => 1,
            'funcid'   => 1,
            'sintomas' => 'Barulho na suspensão',
            'valor_total' => 500.00,
            'itens'    => [
                ['id' => 1, 'qtd' => 2, 'preco' => 250.00]
            ]
        ];

        $response = $this->postJson('/api/os/salvar', $dados);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Ordem de Serviço criada com sucesso!');

        $this->assertDatabaseHas('os', ['clientid' => 1, 'status_atual' => 'Recebida']);
        $this->assertDatabaseHas('os_itens', ['osid' => $response['osid'], 'estoqid' => 1]);
    }

    /** @test */
    public function test_deve_listar_ordens_de_servico_com_joins()
    {
        $os = OrdemServico::create([
            'clientid' => 1, 'carid' => 1, 'funcid' => 1,
            'status_atual' => 'Recebida', 'statid_atual' => 1, 'valor_total' => 100
        ]);

        $response = $this->getJson('/api/os/listar');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nome_cliente' => 'Gustavo Pirolo', 'modelo' => 'Golf GTI']);
    }

    /** @test */
    public function test_deve_avancar_status_para_em_diagnostico()
    {
        $os = OrdemServico::create([
            'clientid' => 1, 'carid' => 1, 'funcid' => 1,
            'status_atual' => 'Recebida', 'statid_atual' => 1
        ]);

        $response = $this->postJson("/api/os/emDiagnostico/{$os->osid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('os', [
            'osid' => $os->osid,
            'status_atual' => 'Em diagnóstico'
        ]);
    }

    /** @test */
    public function test_deve_deletar_os_e_limpar_vinculos()
    {
        $os = OrdemServico::create([
            'clientid' => 1, 'carid' => 1, 'funcid' => 1,
            'status_atual' => 'Recebida', 'statid_atual' => 1
        ]);

        DB::table('os_itens')->insert([
            'osid' => $os->osid, 'estoqid' => 1, 'quantidade' => 1, 'valor_unitario' => 10
        ]);

        $response = $this->postJson("/api/os/deletarOs/{$os->osid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('os', ['osid' => $os->osid]);
        $this->assertDatabaseMissing('os_itens', ['osid' => $os->osid]);
    }

    /** @test */
    public function test_deve_listar_apenas_os_em_diagnostico_ou_recebidas()
    {
        OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 1, 'status_atual' => 'Recebida']);
        OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 3, 'status_atual' => 'Aprovada']);

        $response = $this->getJson('/api/os/listarRecebidos');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    public function test_deve_aprovar_uma_os_mudando_para_em_execucao()
    {
        $os = OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 3, 'status_atual' => 'Aguardando Aprovação']);

        $response = $this->postJson("/api/os/aprovarOs/{$os->osid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('os', [
            'osid' => $os->osid,
            'statid_atual' => 4,
            'status_atual' => 'Em Execução'
        ]);
    }

    /** @test */
    public function test_deve_entregar_os_e_marcar_como_entregue()
    {
        $os = OrdemServico::create(['clientid' => 1, 'carid' => 1, 'funcid' => 1, 'statid_atual' => 5, 'status_atual' => 'Finalizada', 'entregue' => 0]);

        $response = $this->postJson("/api/os/entregar/{$os->osid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('os', [
            'osid' => $os->osid,
            'entregue' => 1
        ]);
    }

    /** @test */
    public function test_nao_deve_criar_os_sem_itens_obrigatorios()
    {
        $response = $this->postJson('/api/os/salvar', [
            'clientid' => 1
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['carid', 'funcid', 'itens']]);
    }

    /** @test */
    public function test_deve_retornar_404_ao_tentar_atualizar_os_inexistente()
    {
        $response = $this->putJson('/api/os/atualizarOs/999', ['clientid' => 1]);
        $response->assertStatus(404);
    }

    /** @test */
    public function test_deve_aprovar_os_pelo_link_externo_do_cliente_e_atualizar_status()
    {
        // 1. Cria uma OS aguardando a decisão do cliente
        $os = OrdemServico::create([
            'clientid'     => 1,
            'carid'        => 1,
            'funcid'       => 1,
            'statid_atual' => 3,
            'status_atual' => 'Aguardando Aprovação',
            'valor_total'  => 500.00
        ]);

        $response = $this->postJson("/api/os/aprovarOsUsuario/{$os->osid}");

        $response->assertStatus(200);
        $response->assertJsonPath('sucesso', true);

        $this->assertDatabaseHas('os', [
            'osid'         => $os->osid,
            'status_atual' => 'Em Execução',
            'statid_atual' => 4
        ]);
    }

    /** @test */
    public function test_deve_reprovar_os_pelo_link_externo_do_cliente_e_cancelar()
    {
        $os = OrdemServico::create([
            'clientid'     => 1,
            'carid'        => 1,
            'funcid'       => 1,
            'statid_atual' => 3,
            'status_atual' => 'Aguardando Aprovação',
            'valor_total'  => 500.00
        ]);

        $response = $this->postJson("/api/os/reprovarOsUsuario/{$os->osid}");

        $response->assertStatus(200);
        $response->assertJsonPath('sucesso', true);

        $this->assertDatabaseHas('os', [
            'osid'         => $os->osid,
            'status_atual' => 'Cancelado',
            'statid_atual' => 7
        ]);
    }

    /** @test */
    public function test_deve_retornar_404_ao_tentar_aprovar_ou_reprovar_uma_os_inexistente()
    {
        $responseAprovar = $this->postJson('/api/os/aprovarOsUsuario/9999');
        $responseAprovar->assertStatus(404);

        $responseReprovar = $this->postJson('/api/os/reprovarOsUsuario/9999');
        $responseReprovar->assertStatus(404);
    }
}
