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
    $mensagemSucesso = "Análise da Ordem de Serviço finalizada com sucesso!";
}

try {
    $osService->statusDiagnostico($osId);
    $osParaAnalise = $osService->buscarPorId($osId);
    if (!$osParaAnalise) {
        throw new Exception("Ordem de Serviço não encontrada.");
    }
} catch (Exception $e) {
    $mensagemErro = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itens = $_POST['itens'] ?? null;
    $total = $_POST['valor_total'] ?? 0;
    $analise = $_POST['analise'] ?? null;

    $os = [
        'itens' => $itens,
        'valor_total' => $total,
        'analise' => $analise
    ];

    function analiseOs(OsServiceInterface $osService, $id, $os)
    {
        $osService->analiseOs($id, $os);
    }

    try {
        analiseOs($osService, $osId, $os);

        header("Location: analise_os.php?");
        exit;
    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();
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
<h3>OS: <?php echo $osParaAnalise->os->osid ?></h3>
<form method="POST" style="max-width: 600px; border: 1px solid #ccc; padding: 20px;">
    <input type="hidden" name="osid" value="<?= $osParaAnalise->os->osid ?>">

    <p>Cliente: <?php echo $osParaAnalise->os->nome_cliente ?> </p>

    <p>Veículo: <?php echo $osParaAnalise->os->modelo ?></p>

    <p>
        <label for="sintomas">Relato do cliente:</label>
        <p id="sintomas" style="border: 1px solid black;"><?=$osParaAnalise->os->sintomas ?></p>
    </p>

    <p>Mecânico: <?php echo $osParaAnalise->os->nome_mecanico ?></p>

    <label for="analise">Análise do Mecânico:</label>
    <textarea name="analise" id="analise" rows="3" style="100%" placeholder="Inserir análise"></textarea> <br>

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
            <?php if (!empty($osParaAnalise->itens)): ?>
                <?php foreach ($osParaAnalise->itens as $item): ?>
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
    <button type="submit">Finalizar análise</button>
</form>

<script>
    $(document).ready(function() {
        let totalGeral = 0;

        $('.btn-remove').each(function() {
            totalGeral += parseFloat($(this).data('subtotal'));
        });

        $('#total-os').text("R$ " + totalGeral.toFixed(2));
        $('#input-total-os').val(totalGeral.toFixed(2));

        $('#busca_estoque').select2({
            minimumInputLength: 2,
            ajax: {
                url: 'http://localhost:8003/api/estoque/buscar',
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
