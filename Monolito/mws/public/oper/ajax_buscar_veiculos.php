<?php
require __DIR__ . '/../../vendor/autoload.php';
use App\Veiculo\Infrastructure\VeiculoRepository;

$termo = $_GET['e'] ?? '';

try {
    $veiculoRepository = new VeiculoRepository();

    $veiculo = $veiculoRepository->buscarPorClienteId($termo);

    header('Content-Type: application/json');
    echo json_encode($veiculo);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}