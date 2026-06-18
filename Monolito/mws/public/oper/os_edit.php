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

$osService = new OsService();
$osId = $_GET['osid'];

$mensagemErro = "";
$mensagemSucesso = "";

if (isset($_GET['sucesso'])) {
    $mensagemSucesso = "Informações atualizadas com sucesso!";
}

try {
    $osParaEditar = $osService->buscarPorId($osId);
    if (!$osParaEditar) {
        throw new Exception("Ordem de Serviço não encontrada.");
    }
} catch (Exception $ex) {
    $mensagemErro = $ex->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'];

    if ($acao == 'editar') {
        $cliente = $_POST['cliente'] ?? null;
        $veiculo = $_POST['veiculo'] ?? null;
        $sintomas = $_POST['sintomas'] ?? null;
        $mecanico = $_POST['tecnico'] ?? null;
        $itens = $_POST['itens'] ?? null;
        $total = $_POST['valor_total'] ?? 0;

        $os = [
                'clientid' => $cliente,
                'carid' => $veiculo,
                'sintomas' => $sintomas,
                'funcid' => $mecanico,
                'itens' => $itens,
                'valor_total' => $total,
        ];

        function atualizarOs(OsServiceInterface $osService, $id, $os) {
            $osService->atualizarOs($id, $os);
        }

        try {
            atualizarOs($osService, $osId, $os);

            header("Location: os_edit.php?osid=" . $osId . "&sucesso=1");
            exit;
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }

    if ($acao == 'deletar') {
        function deletarOs(OsServiceInterface $osService, $id) {
            $osService->deletarOs($id);
        }

        try {
            deletarOs($osService, $osId);

            header("Location: historico_os.php");
            exit;
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }
}

require '../header.php';
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

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

<h2>Editar Ordem de Serviço</h2>
<h3>OS: <?php echo $osParaEditar->os->osid ?></h3>
<form method="POST" style="max-width: 600px; border: 1px solid #ccc; padding: 20px;">
    <input type="hidden" name="osid" value="<?= $osParaEditar->os->osid ?>">

    <p>
        <label for="cliente"><strong style="color:red">*</strong>Cliente</label>
        <select id="cliente" name="cliente" required style="width: 100%">
            <option value="<?= $osParaEditar->os->clientid ?>"><?php echo $osParaEditar->os->nome_cliente ?></option>
        </select>
    </p>

    <label for="veiculo"><strong style="color:red;">*</strong>Veículo</label>
    <select id="veiculo" name="veiculo" required>
        <option value="<?= $osParaEditar->os->carid ?>"><?php echo $osParaEditar->os->modelo ?></option>
    </select> <br>

    <label for="sintomas">Relato do cliente:</label>
    <textarea name="sintomas" id="sintomas" rows="3" style="100%" placeholder="<?=$osParaEditar->os->sintomas ?>"> <?php echo $osParaEditar->os->sintomas ?></textarea> <br>

    <label for="tecnico"><strong style="color:red;">*</strong>Mecânico Responsável:</label>
    <select name="tecnico" id="tecnico" style="width: 100%" required>
        <option value="<?= $osParaEditar->os->funcid ?>"><?php echo $osParaEditar->os->nome_mecanico ?></option>
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
            <?php if (!empty($osParaEditar->itens)): ?>
                <?php foreach ($osParaEditar->itens as $item): ?>
                    <?php $subtotal = $item->qtd * $item->preco; ?>
                    <tr>
                        <td><?= $item->descricao ?></td>
                        <td><?= $item->qtd ?></td>
                        <td>R$ <?= number_format($item->preco, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                        <td>
                            <button type="button" class="btn-remove" data-subtotal="<?= $subtotal ?>" style="color: red; border: none; background: none; cursor:pointer;">Remover</button>
                            <input type="hidden" name="itens[]" value='{"id": "<?= $item->id ?>", "qtd": "<?= $item->qtd ?>", "preco": "<?= $item->preco ?>"}'>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
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
    <button type="submit" name="acao" value="editar">Editar OS</button>
    <button type="submit" name="acao" value="deletar"
            style="background: red; color: white;"
            onclick="return confirm('Tem certeza que deseja excluir esta OS?')">
        Deletar OS
    </button>
</form>

<script>
    $(document).ready(function() {
        let totalGeral = 0;

        $('.btn-remove').each(function() {
            totalGeral += parseFloat($(this).data('subtotal'));
        });

        $('#total-os').text("R$ " + totalGeral.toFixed(2));
        $('#input-total-os').val(totalGeral.toFixed(2));

        $('#cliente').select2({
            minimumInputLength: 3,
            ajax: {
                url: 'ajax_buscar_clientes.php',
                dataType: 'json',
                delay: 250,
                processResults: (data) => ({
                    results: data.map(item => ({ id: item.clientid, text: item.nome }))
                }),
                cache: true
            },
            placeholder: 'Selecione um cliente'
        });

        $('#tecnico').select2({
            minimumInputLength: 3,
            ajax: {
                url: 'ajax_buscar_mecanico.php',
                dataType: 'json',
                delay: 250,
                processResults: (data) => ({
                    results: data.map(item => ({ id: item.funcid, text: item.nome }))
                }),
                cache: true
            },
            placeholder: 'Selecione um mecânico'
        });

        $('#busca_estoque').select2({
            minimumInputLength: 2,
            ajax: {
                url: 'ajax_buscar_estoque.php',
                dataType: 'json',
                delay: 250,
                processResults: (data) => ({
                    results: data.map(item => ({
                        id: item.estoqid,
                        text: `${item.descricao} (R$ ${item.valor_venda})`,
                        preco: item.valor_venda,
                        descricao: item.descricao
                    }))
                })
            }
        });

        $('#cliente').on('select2:select', function (e) {
            const clienteId = e.params.data.id;
            const veiculoSelect = $('#veiculo');
            veiculoSelect.html('<option value="">Carregando veículos...</option>');

            fetch(`ajax_buscar_veiculos.php?e=${clienteId}`)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Selecione o veículo</option>';
                    data.forEach(v => {
                        options += `<option value="${v.carid}">${v.modelo} - ${v.placa}</option>`;
                    });
                    veiculoSelect.html(options);
                });
        });

        $('#btn-adicionar-item').click(function() {
            const itemData = $('#busca_estoque').select2('data')[0];
            const qtd = parseFloat($('#item_qtd').val());

            if (!itemData || !itemData.id) {
                alert("Selecione um item primeiro!");
                return;
            }

            const subtotal = itemData.preco * qtd;
            totalGeral += subtotal;

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
            $('#input-total-os').val(totalGeral.toFixed(2));

            $('#busca_estoque').val(null).trigger('change');
            $('#item_qtd').val(1);
        });

        $(document).on('click', '.btn-remove', function() {
            const sub = parseFloat($(this).data('subtotal'));
            totalGeral -= sub;

            $('#total-os').text("R$ " + totalGeral.toFixed(2));
            $('#input-total-os').val(totalGeral.toFixed(2));
            $(this).closest('tr').remove();
        });

        $(document).on('select2:open', () => {
            document.querySelector('.select2-search__field').focus();
        });
    });
</script>
