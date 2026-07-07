<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\OS\Application\OsService;
use App\OS\Application\OsServiceInterface;
use App\OS\Infrastructure\AprovacaoRepository;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$id = $_GET['id'] ?? null;
$tokenRecebido = $_GET['token'] ?? null;
$mensagemErro = "";
$mensagemSucesso = "";

if (!$id || !$tokenRecebido) {
    http_response_code(400);
    die("<h1>Erro 400: Requisição inválida.</h1>");
}

$salt = $_ENV['SECRET_KEY'] ?? null;
$tokenValido = md5($id . $salt);

if ($tokenRecebido !== $tokenValido) {
    http_response_code(403);
    die("<h1>Erro 403: Acesso Negado. Esta URL foi violada ou expirou!</h1>");
}
try {
    $osRepository = new AprovacaoRepository();

    $os = $osRepository->buscarPorId((int)$id);
    if (!$os) {
        http_response_code(404);
        die("<h1>Erro 404: Ordem de Serviço não encontrada.</h1>");
    }

    $itens = $osRepository->buscarItensPorOsId((int)$id);

    if (isset($itens['descricao'])) {
        $itens = [$itens];
    }
} catch (\Exception $e) {
    http_response_code(500);
    die("<h1>Erro 500: Erro de processamento interno do ecossistema.</h1>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $osService = new OsService();
    $acao = $_POST['acao'];

    if ($acao === 'aprovar') {
        function aprovarOsUsuario(OsServiceInterface $osService, $id) {
            $osService->aprovarOsUsuario($id);
        }

        try {
            aprovarOsUsuario($osService, $id);

            $mensagemSucesso = "Ordem de Serviço $id aprovada com sucesso!";
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }

    if ($acao === 'reprovar') {
        function reprovarOsUsuario(OsServiceInterface $osService, $id) {
            $osService->reprovarOsUsuario($id);
        }

        try {
            reprovarOsUsuario($osService, $id);

            $mensagemSucesso = "Ordem de Serviço $id reprovada com sucesso!";
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>

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

<head><title>Aprovação de Orçamento</title></head>
<body>
<h1>Orçamento da OS #<?php echo htmlspecialchars($id); ?></h1>

<form method="POST" style="max-width: 600px; border: 1px solid #ccc; padding: 20px;">

    <p>
        Veículo: <?php echo htmlspecialchars($itens[0]["modelo"] ?? '') ?>
    </p> <br>

    <?php if (!empty($itens[0]['sintomas'])) : ?>
        <p>Problemas relatados: </p>
        <div style="border: 1px solid #ccc; padding: 20px;">
            <?php echo htmlspecialchars($itens[0]['sintomas']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($itens[0]['analise'])) : ?>
        <p>Análise adicional do mecânico: </p>
        <div style="border: 1px solid #ccc; padding: 20px;">
            <?php echo htmlspecialchars($itens[0]['analise']) ?>
        </div>
    <?php endif; ?>


    <div id="secao-itens" style="margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3><strong style="color:red;">*</strong>Peças e Serviços</h3>

        <table id="tabela-itens-os" border="1" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
            <tr style="background: #f8f9fa;">
                <th>Descrição</th>
                <th>Qtd</th>
                <th>Vl. Unit</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($itens)): ?>
                <?php foreach ($itens as $item): ?>
                    <?php
                    $subtotal = $item['quantidade'] * $item['valor_unitario'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['descricao']) ?></td>
                        <td><?= htmlspecialchars($item['quantidade']) ?></td>
                        <td>R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                <td id="total-os" style="font-weight: bold;">
                    R$ <?= number_format($os['valor_total'], 2, ',', '.') ?>
                </td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <input type="hidden" name="valor_total" id="input-total-os" value="0">
    <button type="submit" name="acao" value="aprovar">Aprovar</button>
    <button type="submit" name="acao" value="reprovar"
            style="background: red; color: white;"
            onclick="return confirm('Tem certeza que deseja reprovar esta OS?')">
        Reprovar
    </button>
</form>
</body>
</html>