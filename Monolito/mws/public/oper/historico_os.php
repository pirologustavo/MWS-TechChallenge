<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

require '../header.php';

echo "HISTORICO?";
?>