<?php
include '../includes/conexao.php';
include '../includes/header.php';

$mensagemSucesso = '';
$mensagemErro    = '';
$modoEdicao      = false;
$produtoEditar   = null;

if (isset($_GET['excluir'])) {
    $idExcluir = (int) $_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id_produto = :id");
        $stmt->execute([':id' => $idExcluir]);
        $mensagemSucesso = "Produto excluído com sucesso!";
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $mensagemErro = "Não é possível excluir este produto porque ele está vinculado a um ou mais orçamentos no sistema. Remova os orçamentos que contêm este produto antes de excluí-lo.";
        } else {
            $mensagemErro = "Erro ao excluir produto: " . $e->getMessage();
        }
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id_produto = :id LIMIT 1");
    $stmt->execute([':id' => $idEditar]);
    $produtoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($produtoEditar) {
        $modoEdicao = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeProduto = trim($_POST['nome_produto'] ?? '');
    $categoria   = trim($_POST['categoria'] ?? 'Galpão Metálico');
    $precoBase   = floatval($_POST['preco_base'] ?? 0);
    $quantidade  = (int) ($_POST['quantidade_disponivel'] ?? 0);
    $idPost      = (int) ($_POST['id_produto'] ?? 0);

    if ($nomeProduto === '' || $precoBase <= 0) {
        $mensagemErro = "Preencha todos os campos obrigatórios. O preço deve ser maior que zero.";
    } else {
        try {
            if ($idPost > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE produtos SET nome_produto = :nome, categoria = :categoria, preco_base = :preco, quantidade_disponivel = :qtd WHERE id_produto = :id"
                );
                $stmt->execute([
                    ':nome'      => $nomeProduto,
                    ':categoria' => $categoria,
                    ':preco'     => $precoBase,
                    ':qtd'       => $quantidade,
                    ':id'        => $idPost,
                ]);
                $mensagemSucesso = "Produto atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO produtos (nome_produto, categoria, preco_base, quantidade_disponivel) VALUES (:nome, :categoria, :preco, :qtd)"
                );
                $stmt->execute([
                    ':nome'      => $nomeProduto,
                    ':categoria' => $categoria,
                    ':preco'     => $precoBase,
                    ':qtd'       => $quantidade,
                ]);
                $mensagemSucesso = "Produto cadastrado com sucesso!";
            }
            $modoEdicao    = false;
            $produtoEditar = null;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Regra de Negócio') || $e->getCode() === '45000') {
                $mensagemErro = "Regra violada pelo banco de dados: " . $e->getMessage();
            } else {
                $mensagemErro = "Erro ao salvar produto: " . $e->getMessage();
            }
        }
    }
}

$produtos = $pdo->query("SELECT * FROM produtos ORDER BY categoria ASC, nome_produto ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Produtos</h1>
        <div class="d-flex gap-2">
            <a href="clientes.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left-circle me-1"></i>Clientes</a>
            <a href="orcamentos.php" class="btn btn-outline-dark"><i class="bi bi-arrow-right-circle me-1"></i>Orçamentos</a>
        </div>
    </div>

    <?php if ($mensagemSucesso !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensagemSucesso); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($mensagemErro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5 border-0">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3">
                <?php echo $modoEdicao ? '<i class="bi bi-pencil-square me-2"></i>Editar Produto' : '<i class="bi bi-plus-circle me-2"></i>Novo Produto'; ?>
            </h5>
            <form method="POST" action="produtos.php" class="row g-3">
                <input type="hidden" name="id_produto" value="<?php echo $modoEdicao ? (int)$produtoEditar['id_produto'] : 0; ?>">
                <div class="col-md-6">
                    <label class="form-label">Nome do produto <span class="text-danger">*</span></label>
                    <input type="text" name="nome_produto" class="form-control" required
                           value="<?php echo $modoEdicao ? htmlspecialchars($produtoEditar['nome_produto']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <input type="text" name="categoria" class="form-control"
                           value="<?php echo $modoEdicao ? htmlspecialchars($produtoEditar['categoria']) : 'Galpão Metálico'; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Preço base (R$) <span class="text-danger">*</span></label>
                    <input type="number" name="preco_base" class="form-control" step="0.01" min="0.01" required
                           value="<?php echo $modoEdicao ? number_format((float)$produtoEditar['preco_base'], 2, '.', '') : ''; ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Estoque</label>
                    <input type="number" name="quantidade_disponivel" class="form-control" min="0"
                           value="<?php echo $modoEdicao ? (int)$produtoEditar['quantidade_disponivel'] : 0; ?>">
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <?php if ($modoEdicao): ?>
                        <a href="produtos.php" class="btn btn-outline-secondary">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-save me-1"></i><?php echo $modoEdicao ? 'Salvar Alterações' : 'Cadastrar Produto'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><i class="bi bi-boxes me-2"></i>Produtos Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço base</th>
                            <th>Estoque</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produtos) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhum produto cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?php echo (int)$produto['id_produto']; ?></td>
                                    <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                    <td><?php echo htmlspecialchars($produto['categoria']); ?></td>
                                    <td>R$ <?php echo number_format((float)$produto['preco_base'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php
                                            $qtd = (int)$produto['quantidade_disponivel'];
                                            $badge = $qtd === 0 ? 'bg-danger' : ($qtd <= 5 ? 'bg-warning text-dark' : 'bg-success');
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo $qtd; ?> un.</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="produtos.php?editar=<?php echo (int)$produto['id_produto']; ?>"
                                           class="btn btn-sm btn-outline-warning me-1" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="produtos.php?excluir=<?php echo (int)$produto['id_produto']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir o produto <?php echo addslashes(htmlspecialchars($produto['nome_produto'])); ?>?')">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
