<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\OS\Application\OsService;
use App\OS\Application\OsServiceInterface;
use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$mensagemErro = "";
$mensagemSucesso = "";

$osService = new OsService();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente = $_POST['cliente'];
    $veiculo = $_POST['veiculo'];
    $sintomas = $_POST['sintomas'];
    $tecnico = $_POST['tecnico'];
    $itens = $_POST['itens'] ?? [];
    $total = $_POST['valor_total'] ?? 0;

    $os = [
        'clientid' => $cliente,
        'carid' => $veiculo,
        'sintomas' => $sintomas,
        'funcid' => $tecnico,
        'itens' => $itens,
        'valor_total' => $total,
    ];

    function salvarOs(OsServiceInterface $osService, $os){
        $osService->salvar($os);
    }

    try {
        salvarOs($osService, $os);

        $mensagemSucesso = "Ordem de Serviço criada com sucesso!";
    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();
    }
}

require '../header.php';
?>

<?php if ($mensagemErro): ?>
    <div style="background: #ffcccc; color: #990000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <strong>Erro:</strong> <?php  echo $mensagemErro; ?>
    </div>
<?php endif; ?>

<?php if ($mensagemSucesso): ?>
    <div style="background: #ccffcc; color: #006600; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <strong>Sucesso:</strong> <?php  echo $mensagemSucesso; ?>
    </div>
<?php endif; ?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<h2>Abertura de OS</h2>

<form method="post">
    <label for="cliente"><strong style="color:red">*</strong>Cliente</label>
    <select id="cliente" name="cliente" required style="width: 100%">
        <option value="">Digite o nome do cliente...</option>
    </select>

    <label for="veiculo"><strong style="color:red;">*</strong>Veículo</label>
    <select id="veiculo" name="veiculo" required>
        <option value="">Selecione o veículo</option>
    </select> <br>

    <label for="sintomas">Relato do cliente:</label>
    <textarea name="sintomas" id="sintomas" rows="3" style="100%" placeholder="Descreva o que o cliente relatou..."></textarea> <br>

    <label for="tecnico"><strong style="color:red;">*</strong>Mecânico Responsável:</label>
    <select name="tecnico" id="tecnico" style="width: 100%" required>
        <option value="">Digite o nome do mecânico</option>
    </select>

    <div id="secao-itens" style="margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3><strong style="color:red;">*</strong>Peças e Serviços</h3>

        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <select id="busca_estoque" style="width: 70%">
                <option value="">Buscar peça ou serviço no estoque...</option>
            </select>
            <input type="number" id="item_qtd" value="1" min="1" style="width: 60px;">
            <button type="button" id="btn-adicionar-item" style="background: #007bff; color: white; border: none; padding: 5px 15px; border-radius: 3px; cursor: pointer;">
                + Adicionar
            </button>
        </div>

        <table id="tabela-itens-os" border="1" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
            <tr style="background: #f8f9fa;">
                <th>Descrição</th>
                <th>Qtd</th>
                <th>Vl. Unit</th>
                <th>Subtotal</th>
                <th>Ação</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                <td id="total-os" style="font-weight: bold;">R$ 0,00</td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <input type="hidden" name="valor_total" id="input-total-os" value="0">
    <button type="submit">Criar OS</button>
</form>

<script>
    $(document).ready(function() {
        $('#cliente').select2({
            minimumInputLength: 3,
            ajax: {
                url: 'ajax_buscar_clientes.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.clientid,
                                text: item.nome
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: 'Selecione um cliente',
            language: {
                inputTooShort: function() { return "Digite 3 ou mais letras..."; }
            }
        });
    });

    $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
    });

    $('#cliente').on('select2:select', function (e) {
        const clienteId = e.params.data.id;
        const veiculoSelect = $('#veiculo');

        veiculoSelect.html('<option value="">Carregando veículos...</option>');

        fetch(`http://localhost:8002/api/veiculos/buscarVeiculoCliente/${clienteId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Selecione o veículo</option>';

                data.forEach(veiculo => {
                    options += `<option value="${veiculo.carid}">${veiculo.modelo} - ${veiculo.placa}</option>`;
                });

                veiculoSelect.html(options);
            })
            .catch(error => {
                console.error('Erro ao buscar veículos:', error);
                veiculoSelect.html('<option value="">Erro ao carregar veículos</option>');
            });
    });

    $(document).ready(function() {
        $('#tecnico').select2({
            minimumInputLength: 3,
            ajax: {
                url: 'http://localhost:8004/api/funcionario/buscarMecanico',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.funcid,
                                text: item.nome
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: 'Selecione um mecânico',
            language: {
                inputTooShort: function() { return "Digite 3 ou mais letras..."; }
            }
        });
    });

    $(document).ready(function() {
        $('#busca_estoque').select2({
            minimumInputLength: 2,
            ajax: {
                url: 'http://localhost:8003/api/estoque/buscar',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.estoqid,
                                text: item.descricao + " (R$ " + item.valor_venda + ")",
                                preco: item.valor_venda,
                                descricao: item.descricao
                            };
                        })
                    };
                }
            }
        });

        let totalGeral = 0;

        $('#btn-adicionar-item').click(function() {
            const itemData = $('#busca_estoque').select2('data')[0];
            const qtd = $('#item_qtd').val();

            if (!itemData || !itemData.id) {
                alert("Selecione um item primeiro!");
                return;
            }

            const subtotal = itemData.preco * qtd;
            totalGeral += subtotal;

            $('#total-os').text("R$ " + totalGeral.toFixed(2));
            $('#input-total-os').val(totalGeral.toFixed(2));

            const novaLinha = `
            <tr>
                <td>${itemData.descricao}</td>
                <td>${qtd}</td>
                <td>R$ ${parseFloat(itemData.preco).toFixed(2)}</td>
                <td>R$ ${subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn-remove" data-subtotal="${subtotal}" style="color: red; border: none; background: none; cursor:pointer;">Remover</button>
                    <input type="hidden" name="itens[]" value='{"id": "${itemData.id}", "qtd": "${qtd}", "preco": "${itemData.preco}"}'>
                </td>
            </tr>
        `;

            $('#tabela-itens-os tbody').append(novaLinha);
            $('#total-os').text("R$ " + totalGeral.toFixed(2));

            $('#busca_estoque').val(null).trigger('change');
            $('#item_qtd').val(1);
        });

        $(document).on('click', '.btn-remove', function() {
            const sub = $(this).data('subtotal');
            totalGeral -= sub;

            $('#total-os').text("R$ " + totalGeral.toFixed(2));
            $('#input-total-os').val(totalGeral.toFixed(2));

            $('#total-os').text("R$ " + totalGeral.toFixed(2));
            $(this).closest('tr').remove();
        });
    });
</script>