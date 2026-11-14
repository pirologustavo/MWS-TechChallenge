<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Saga\StockAdjustmentResultHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class OrdemServicoControllerTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config(['services.internal' => [
            'cliente' => 'http://cliente.test',
            'veiculo' => 'http://veiculo.test',
            'funcionario' => 'http://funcionario.test',
            'estoque' => 'http://estoque.test',
        ]]);
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/api/internal/v1/clientes')) {
                return Http::response([['clientid' => 1, 'nome' => 'Gustavo Pirolo', 'email' => 'gustavo@example.test']], 200);
            }
            if (str_contains($url, '/api/internal/v1/veiculos')) {
                return Http::response([['carid' => 1, 'clientid' => 1, 'modelo' => 'Golf GTI', 'placa' => 'ABC-1234']], 200);
            }
            if (str_contains($url, '/api/internal/v1/funcionarios')) {
                return Http::response([['funcid' => 1, 'nome' => 'Mecânico Claudio', 'cargo' => 'Mecânico']], 200);
            }
            if (str_contains($url, '/api/internal/v1/itens-estoque')) {
                return Http::response([['estoqid' => 1, 'descricao' => 'Amortecedor', 'tipo' => 'peca', 'quantidade_atual' => 10, 'valor_venda' => 250, 'ativo' => true]], 200);
            }
            return Http::response(['message' => 'Estoque ajustado com sucesso.'], 200);
        });
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

    /**
     * BDD: Given valid references and available stock, when the workshop
     * completes the OS lifecycle, then every state transition is persisted.
     */
    #[Group('bdd')]
    public function test_bdd_fluxo_completo_da_os_da_abertura_a_entrega(): void
    {
        Mail::fake();

        // Given: client, vehicle, mechanic and stock are available (faked by setUp).
        // When: open the service order.
        $opened = $this->postJson('/api/os/salvar', [
            'clientid' => 1,
            'carid' => 1,
            'funcid' => 1,
            'sintomas' => 'Ruído na suspensão',
            'itens' => [['id' => 1, 'qtd' => 1]],
        ]);
        $opened->assertCreated();
        $osid = $opened->json('osid');
        $this->completePendingSaga($osid);

        // And: diagnose the vehicle and submit an estimate for customer approval.
        $this->postJson("/api/os/emDiagnostico/{$osid}")->assertOk();
        $this->postJson("/api/os/analiseOs/{$osid}", [
            'analise' => 'Troca do amortecedor',
            'itens' => [['id' => 1, 'qtd' => 1, 'preco' => 250]],
        ])->assertOk();

        // And: customer approves; the workshop finishes and delivers the OS.
        $this->postJson("/api/os/aprovarOsUsuario/{$osid}")->assertOk();
        $this->postJson("/api/os/finalizarOs/{$osid}")->assertOk();
        $this->postJson("/api/os/entregar/{$osid}")->assertOk();

        // Then: final state and item history are retained.
        $this->assertDatabaseHas('os', [
            'osid' => $osid,
            'status_atual' => 'Entregue',
            'statid_atual' => 6,
            'entregue' => 1,
        ]);
        $this->assertDatabaseHas('os_itens', ['osid' => $osid, 'estoqid' => 1, 'quantidade' => 1]);
    }

    private function completePendingSaga(int $osid): void
    {
        $operation = DB::table('saga_operations')->where('osid', $osid)->where('state', 'PENDING')->latest()->first();
        $this->assertNotNull($operation, 'A abertura da OS deve iniciar uma Saga pendente.');

        app(StockAdjustmentResultHandler::class)->handle([
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_type' => 'stock.adjustment.succeeded',
            'saga_id' => $operation->id,
            'operation_id' => $operation->id,
            'osid' => $osid,
            'request_event_id' => $operation->request_event_id,
            'items' => [['id' => 1, 'qtd' => -1]],
        ]);
    }

    /** @test */
    public function test_deve_listar_ordens_de_servico_com_dados_historicos()
    {
        $os = OrdemServico::create([
            'clientid' => 1, 'carid' => 1, 'funcid' => 1,
            'status_atual' => 'Recebida', 'statid_atual' => 1, 'valor_total' => 100,
            'cliente_nome_snapshot' => 'Gustavo Pirolo',
            'veiculo_modelo_snapshot' => 'Golf GTI',
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
            'osid' => $os->osid, 'estoqid' => 1, 'quantidade' => 1, 'valor_unitario' => 10, 'subtotal' => 10
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
