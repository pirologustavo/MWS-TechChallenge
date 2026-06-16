<?php
require __DIR__ . "/../../../vendor/autoload.php";
use App\Cliente\Infrastructure\ClienteRepository;

$termo = $_GET['q'] ?? '';

try {
    $clienteRepository = new ClienteRepository();

    $clientes = $clienteRepository->buscarPorNome($termo);

    header('Content-Type: application/json');
    echo json_encode($clientes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}