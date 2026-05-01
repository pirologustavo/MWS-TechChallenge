<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Cliente\Application\ClienteService;
use App\Seguranca\Application\AuthService;
use App\Veiculo\Application\VeiculoService;
use App\Veiculo\Application\VeiculoServiceInterface;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$mensagemErro = "";
$mensagemSucesso = "";

$veiculoService = new VeiculoService();
$marcas = $veiculoService::MARCAS;

$clienteService = new ClienteService();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modelo = $_POST['car_mod'] ?? null;
    $placa = $_POST['car_placa'] ?? null;
    $marca = $_POST['car_marca'] ?? null;
    $ano = $_POST['car_ano'] ?? null;
    $cliente = $_POST['clientid'] ?? null;

    $veiculo = [
        'modelo' => $modelo,
        'placa' => $placa,
        'marca' => $marca,
        'ano' => $ano,
        'clientid' => $cliente,
    ];

    /**
     * @throws Exception
     */
    function salvarVeiculo(VeiculoServiceInterface $veiculoService, array $veiculo) {
        $veiculoService->salvar($veiculo);
    }

    try {
        salvarVeiculo($veiculoService, $veiculo);

        $mensagemSucesso = "Veículo cadastrado com sucesso!";
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

<?php if ($mensagemSucesso): ?>
    <div style="background: #ccffcc; color: #006600; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <strong>Sucesso:</strong> <?php  echo $mensagemSucesso; ?>
    </div>
<?php endif; ?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<h1>Cadastro Veículo</h1>

<form method="POST">
    <label for="modelo"><strong style="color:red">*</strong> Modelo</label>
    <input type="text" id="modelo" name="car_mod"
           minlength="2" maxlength="50"
           placeholder="Ex: Civic"
           required>

    <label for="placa"><strong style="color:red">*</strong> Placa</label>
    <input type="text"
           id="placa"
           name="car_placa"
           placeholder="ABC1D23"
           pattern="^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$"
           title="Digite uma placa válida (Padrão Mercosul ou Antigo)"
           oninput="this.value = this.value.toUpperCase()"
           required>

    <label for="marca"><strong style="color:red">*</strong> Marca</label>
    <select id="marca" name="car_marca" required>
        <option value="">Selecione...</option>
        <?php foreach ($marcas as $marca): ?>
            <option value="<?php echo $marca?>"><?php echo $marca;?></option>
        <?php endforeach; ?>
    </select>

    <label for="ano"><strong style="color:red">*</strong> Ano</label>
    <select id="ano" name="car_ano" required>
        <option value="">Selecione o ano</option>
        <?php
        $anoAtual = date('Y');
        for ($i = $anoAtual + 1; $i >= 1950; $i--) {
            echo "<option value='$i'>$i</option>";
        }
        ?>
    </select>

    <label for="cliente_select"><strong style="color:red">*</strong> Proprietário</label>
    <select id="cliente_select" name="clientid" required>
        <option value="">Digite o nome do proprietário...</option>
    </select>

    <button>Cadastrar</button>
</form>

<script>
    $(document).ready(function() {
        $('#cliente_select').select2({
            minimumInputLength: 3,
            ajax: {
                url: 'http://localhost:8001/api/clientes/buscar',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.clientid,
                                text: item.nome
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: 'Selecione um proprietário',
            language: {
                inputTooShort: function() { return "Digite 3 ou mais letras..."; }
            }
        });
    });

    $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
    });
</script>