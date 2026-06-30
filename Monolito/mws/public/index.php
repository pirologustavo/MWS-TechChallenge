<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');

use App\Seguranca\Application\SegurancaService;
use App\Seguranca\Application\SegurancaServiceInterface;

$dotenv->load();
$mensagem = "";

$usuario = $_POST['user'] ?? null;
$senha = $_POST['pass'] ?? null;

$segurancaService = new SegurancaService();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $segurancaService = new SegurancaService();
    try {
        $token = $segurancaService->validarLogin($usuario, $senha);
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

    if (!$token) {
        $mensagem = "Usuário ou senha inválidos!";
        exit;
    }

    setcookie("token", $token, time() + 3600, "/", "", false, true);
    header("Location: /oper/index.php");
    exit;
}
?>
<form method="POST">
    <div>
        <label for="user">Usuário</label>
        <input id="user" name="user">
    </div>
    <div>
        <label for="pass">Senha</label>
        <input id="pass" name="pass" type="password">
    </div>
    <p><strong><?= $mensagem ?></strong></p>
    <button>Login</button>
</form>

