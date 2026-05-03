<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}
?>

<style>
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        padding: 20px;
        max-width: 800px;
    }

    .menu-item {
        background-color: #2c3e50;
        text-decoration: none;
        color: white;
        border: none;
        padding: 20px;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 80px;
        margin: 10px;
    }

    .menu-item:hover {
        background-color: #34495e;
    }

    .cabecalho {
        text-align: center;
        font-weight: bold;
    }

    @media (max-width: 600px) {
        .menu-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="menu-grid">
    <div class="os">
        <p class="cabecalho">Ordem de Serviço</p>
        <a href="abertura_os.php" class="menu-item">Abertura de OS</a>
        <a href="historico_os.php" class="menu-item">Histórico de OS</a>
        <a href="analise_os.php" class="menu-item">OS Recebidas</a>
        <a href="aprova_os.php" class="menu-item">Aprovar OS</a>
        <a href="finalizar_os.php" class="menu-item">Finalizar OS</a>
    </div>
    <div class="cadastro">
        <p class="cabecalho">Cadastro</p>
        <a href="cadastro_cliente.php" class="menu-item">Cadastrar Cliente</a>
        <a href="editar_cliente.php" class="menu-item">Editar Cliente</a>
        <a href="cadastro_veiculo.php" class="menu-item">Cadastrar Veículo</a>
        <a href="editar_veiculo.php" class="menu-item">Editar Veiculo</a>
    </div>
    <div class="estoque">
        <p class="cabecalho">Estoque</p>
        <a href="estoque.php" class="menu-item">Gerenciar Estoque</a>
    </div>
</div>