<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

use App\OS\Application\OsService;
use App\OS\Application\OsServiceInterface;
use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$mensagemErro = "";

$osService = new OsService();

function listarTodos(OsServiceInterface $osService)
{
    return $osService->listarTodos();
}

try {
    $relatorio = listarTodos($osService);
} catch (Exception $ex) {
    $mensagemErro = $ex->getMessage();
}

require '../header.php';
?>

<?php if ($mensagemErro): ?>
    <div style="background: #ffcccc; color: #990000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <strong>Erro:</strong> <?php  echo $mensagemErro; ?>
    </div>
<?php endif; ?>

<h2>Ordens de Serviço</h2>
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
                <a href="os_edit.php?osid=<?= $os->osid ?>"
                   style="padding: 5px 10px; background: orange; color: white; text-decoration: none; border-radius: 3px;">
                    Editar
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>