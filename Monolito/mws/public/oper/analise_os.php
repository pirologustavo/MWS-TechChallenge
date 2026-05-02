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