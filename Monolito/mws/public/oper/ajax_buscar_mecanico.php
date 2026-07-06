<?php
require __DIR__ . '/../../vendor/autoload.php';
use App\Funcionario\Infrastructure\FuncionarioRepository;

$termo = $_GET['q'] ?? '';

try {
    $funcionarioRepository = new FuncionarioRepository();

    $funcionario = $funcionarioRepository->buscarMecanico($termo);

    header('Content-Type: application/json');
    echo json_encode($funcionario);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}