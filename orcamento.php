<?php include "includes/header.php"; ?>
<?php include "includes/conexao.php"; ?>

<?php
$sqlProdutos = "SELECT * FROM produtos ORDER BY id_produto";
$stmtProdutos = $pdo->query($sqlProdutos);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

$nomeCliente = $_POST["nome"] ?? "";
$telefoneCliente = $_POST["telefone"] ?? "";
$emailCliente = $_POST["email"] ?? "";
$produtoId = $_POST["produto"] ?? $_GET["produto_id"] ?? "";
$quantidade = $_POST["quantidade"] ?? "";

function formatarDinheiro($valor)
{
    return "R$ " . number_format($valor, 2, ",", ".");
}

$mensagemErro = "";
$mensagemSucesso = "";
$resultadoOrcamento = null;

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $quantidadeFloat = floatval($quantidade);

    if (trim($nomeCliente) === "" || trim($telefoneCliente) === "" || trim($emailCliente) === "") {
        $mensagemErro = "Preencha todos os dados do cliente.";
    } elseif ($produtoId === "" || $produtoId === null) {
        $mensagemErro = "Selecione um produto para o orçamento.";
    } elseif ($quantidadeFloat <= 0) {
        $mensagemErro = "A quantidade precisa ser maior que zero.";
    } else {
        $produtoSelecionado = null;
        foreach ($produtos as $produto) {
            if ($produto["id_produto"] == $produtoId) {
                $produtoSelecionado = $produto;
                break;
            }
        }

        if ($produtoSelecionado === null) {
            $mensagemErro = "Produto selecionado não encontrado.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmtCliente = $pdo->prepare("SELECT id_cliente FROM clientes WHERE email = :email LIMIT 1");
                $stmtCliente->execute([":email" => $emailCliente]);
                $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

                if ($cliente) {
                    $clienteId = $cliente["id_cliente"];
                } else {
                    $stmtInserirCliente = $pdo->prepare(
                        "INSERT INTO clientes (nome, telefone, email) VALUES (:nome, :telefone, :email)"
                    );
                    $stmtInserirCliente->execute([
                        ":nome" => $nomeCliente,
                        ":telefone" => $telefoneCliente,
                        ":email" => $emailCliente,
                    ]);
                    $clienteId = $pdo->lastInsertId();
                }

                $stmtOrcamento = $pdo->prepare(
                    "INSERT INTO orcamentos (id_cliente, data_solicitacao, status) VALUES (:id_cliente, CURDATE(), 'Pendente')"
                );
                $stmtOrcamento->execute([
                    ":id_cliente" => $clienteId
                ]);
                $orcamentoId = $pdo->lastInsertId();

                $stmtOrcamentoItem = $pdo->prepare(
                    "INSERT INTO orcamento_itens (id_orcamento, id_produto, quantidade_solicitada) VALUES (:id_orcamento, :id_produto, :quantidade_solicitada)"
                );
                $stmtOrcamentoItem->execute([
                    ":id_orcamento" => $orcamentoId,
                    ":id_produto" => $produtoSelecionado["id_produto"],
                    ":quantidade_solicitada" => $quantidadeFloat,
                ]);

                $pdo->commit();

                $totalBruto = $produtoSelecionado["preco_base"] * $quantidadeFloat;
                $resultadoOrcamento = [
                    "id" => $orcamentoId,
                    "produto" => $produtoSelecionado,
                    "quantidade" => $quantidadeFloat,
                    "total_bruto" => $totalBruto,
                ];

                $mensagemSucesso = "Orçamento registrado com sucesso. Número #{$orcamentoId}.";
            } catch (PDOException $erro) {
                $pdo->rollBack();
                $mensagemErro = "Erro ao salvar orçamento: " . $erro->getMessage();
            }
        }
    }
}
?>

<main class="container mt-5">
    <h1 class="mb-3">Solicitar Orçamento</h1>

    <p class="lead">Selecione um de nossos galpões a pronta entrega ou a opção de projeto sob medida.</p>

    <?php if ($mensagemErro !== "") { ?>
        <div class="alert alert-danger"><?php echo $mensagemErro; ?></div>
    <?php } ?>

    <?php if ($mensagemSucesso !== "") { ?>
        <div class="alert alert-success"><?php echo $mensagemSucesso; ?></div>
    <?php } ?>

    <section class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Solicitar orçamento</h5>
            <form method="POST" action="orcamento.php" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome completo</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($nomeCliente); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($telefoneCliente); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emailCliente); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Produto / Orçamento</label>
                    <select name="produto" class="form-select">
                        <option value="" disabled <?php echo ($produtoId === "") ? "selected" : ""; ?>>Selecione o que deseja cotar</option>
                        <?php foreach ($produtos as $produto) { ?>
                            <option value="<?php echo $produto["id_produto"]; ?>" <?php if ((string)$produto["id_produto"] === (string)$produtoId) echo "selected"; ?>><?php echo htmlspecialchars($produto["nome_produto"]); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantidade</label>
                    <input type="number" name="quantidade" class="form-control" step="1" min="1" value="<?php echo htmlspecialchars($quantidade); ?>">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-warning">Enviar orçamento</button>
                </div>
            </form>
        </div>
    </section>

    <?php if ($resultadoOrcamento !== null) { ?>
        <section class="alert alert-success">
            <h5>Resumo do orçamento</h5>
            <p><strong>Orçamento #<?php echo $resultadoOrcamento["id"]; ?></strong></p>
            <p><strong>Produto:</strong> <?php echo htmlspecialchars($resultadoOrcamento["produto"]["nome_produto"]); ?></p>
            <p><strong>Quantidade:</strong> <?php echo htmlspecialchars($resultadoOrcamento["quantidade"]); ?></p>
            <p><strong>Estimativa base:</strong> <?php echo formatarDinheiro($resultadoOrcamento["total_bruto"]); ?> <small class="text-muted">(Sujeito a alteração após projeto final)</small></p>
        </section>
    <?php } ?>
</main>

<?php include "includes/footer.php"; ?>
