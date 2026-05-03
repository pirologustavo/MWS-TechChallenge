<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Interface\OsStatusInterface;
use App\OS\Application\OsService;
use App\OS\Application\OsServiceInterface;
use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$mensagemErro = "";

$osService = new OsService();

function listarEmExecucao(OsServiceInterface $osService)
{
    return $osService->listarEmExecucao();
}

try {
    $relatorio = listarEmExecucao($osService);
} catch (Exception $ex) {
    $mensagemErro = $ex->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['os_json'])) {
    $dadosOs = json_decode($_POST['os_json'], true);

    $idParaProcessar = $dadosOs[0];
    $statusAtual     = $dadosOs[1];

    function finalizarOs(OsServiceInterface $osService, $os){
        $osService->finalizarOs($os);
    }

    function entregarOs(OsServiceInterface $osService, $os){
        $osService->entregarOs($os);
    }

    try {
        if ($statusAtual == OsServiceInterface::EM_EXECUCAO[1]) {
            $osService->finalizarOs($idParaProcessar);
            $mensagemSucesso = "OS #$idParaProcessar finalizada!";
        }

        if ($statusAtual == OsServiceInterface::FINALIZADO[1]) {
            $osService->entregarOs($idParaProcessar);
            $mensagemSucesso = "OS #$idParaProcessar entregue ao cliente!";
        }

        $relatorio = listarEmExecucao($osService);
        $mensagemSucesso = "Ordem de Serviço $idParaProcessar aprovada com sucesso!";
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
                    <input type="hidden" name="os_json" value='<?= htmlspecialchars(json_encode([$os->osid, $os->statid_atual])) ?>'>
                    <button type="submit" name="btn_finaliza"
                            style="padding: 5px 10px;
                            <?php if ($os->statid_atual == OsServiceInterface::EM_EXECUCAO[1]): ?>
                                background: orange;
                            <?php endif ?>
                            <?php if ($os->statid_atual == OsServiceInterface::FINALIZADO[1]): ?>
                                background: green;
                            <?php endif ?>
                                color: white;
                                border: none; border-radius: 3px; cursor: pointer;">
                        <?php if ($os->statid_atual == OsServiceInterface::EM_EXECUCAO[1]): ?>
                            Finalizar serviço
                        <?php endif ?>
                        <?php if ($os->statid_atual == OsServiceInterface::FINALIZADO[1]): ?>
                            Entregar ao cliente
                        <?php endif ?>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

