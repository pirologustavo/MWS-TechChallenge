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

$osService = new OsService();

function listarAguardandoAprovacao(OsServiceInterface $osService)
{
    return $osService->listarAguardandoAprovacao();
}

try {
    $relatorio = listarAguardandoAprovacao($osService);
} catch (Exception $ex) {
    $mensagemErro = $ex->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['osid'])) {
    $idParaAprovar = $_POST['osid'];
    function aprovarOs(OsServiceInterface $osService, $id){
        $osService->aprovarOs($id);
    }

    try {
        aprovarOs($osService, $idParaAprovar);

        $relatorio = listarAguardandoAprovacao($osService);
        $mensagemSucesso = "Ordem de Serviço $idParaAprovar aprovada com sucesso!";
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

<h2>Aprovar Ordem de Serviço</h2>

<table border="1" style="width:100%; text-align:left; border-collapse: collapse;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th>ID</th>
        <th>Cliente</th>
        <th>Veículo</th>
        <th>Mecânico</th>
        <th>Ação</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($relatorio as $os): ?>
        <tr>
            <td><?= $os->osid ?></td>
            <td><?= $os->nome_cliente ?></td>
            <td><?= $os->modelo ?></td>
            <td><?= $os->nome_funcionario ?></td>
            <td style="padding: 10px">
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="osid" value="<?= $os->osid ?>">
                    <button type="submit" name="btn_aprovar"
                            style="padding: 5px 10px; background: orange; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        Aprovar
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

