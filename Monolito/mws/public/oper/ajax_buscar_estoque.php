<?php
require __DIR__ . '/../../vendor/autoload.php';
use App\Estoque\Infrastructure\EstoqueRepository;

$termo = $_GET['q'] ?? '';

try {
    $estoqueRepository = new EstoqueRepository();

    $estoque = $estoqueRepository->buscarEstoque($termo);

    header('Content-Type: application/json');
    echo json_encode($estoque);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}