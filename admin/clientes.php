<?php
include '../includes/conexao.php';
include '../includes/header.php';

$mensagemSucesso = '';
$mensagemErro    = '';
$modoEdicao      = false;
$clienteEditar   = null;

if (isset($_GET['excluir'])) {
    $idExcluir = (int) $_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $idExcluir]);
        $mensagemSucesso = "Cliente excluído com sucesso!";
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $mensagemErro = "Não é possível excluir este cliente porque ele possui orçamentos vinculados no sistema. Exclua os orçamentos antes de remover o cliente.";
        } else {
            $mensagemErro = "Erro ao excluir cliente: " . $e->getMessage();
        }
    }
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1");
    $stmt->execute([':id' => $idEditar]);
    $clienteEditar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($clienteEditar) {
        $modoEdicao = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $cidade   = trim($_POST['cidade'] ?? 'São Paulo');
    $idPost   = (int) ($_POST['id_cliente'] ?? 0);

    if ($nome === '' || $telefone === '' || $email === '') {
        $mensagemErro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            if ($idPost > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email, cidade = :cidade WHERE id_cliente = :id"
                );
                $stmt->execute([
                    ':nome'     => $nome,
                    ':telefone' => $telefone,
                    ':email'    => $email,
                    ':cidade'   => $cidade,
                    ':id'       => $idPost,
                ]);
                $mensagemSucesso = "Cliente atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO clientes (nome, telefone, email, cidade) VALUES (:nome, :telefone, :email, :cidade)"
                );
                $stmt->execute([
                    ':nome'     => $nome,
                    ':telefone' => $telefone,
                    ':email'    => $email,
                    ':cidade'   => $cidade,
                ]);
                $mensagemSucesso = "Cliente cadastrado com sucesso!";
            }
            $modoEdicao    = false;
            $clienteEditar = null;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $mensagemErro = "Já existe um cliente cadastrado com este e-mail. Utilize um e-mail diferente.";
            } else {
                $mensagemErro = "Erro ao salvar cliente: " . $e->getMessage();
            }
        }
    }
}

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Clientes</h1>
        <a href="produtos.php" class="btn btn-outline-dark"><i class="bi bi-arrow-right-circle me-1"></i>Ir para Produtos</a>
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
                <?php echo $modoEdicao ? '<i class="bi bi-pencil-square me-2"></i>Editar Cliente' : '<i class="bi bi-person-plus me-2"></i>Novo Cliente'; ?>
            </h5>
            <form method="POST" action="clientes.php" class="row g-3">
                <input type="hidden" name="id_cliente" value="<?php echo $modoEdicao ? (int)$clienteEditar['id_cliente'] : 0; ?>">
                <div class="col-md-6">
                    <label class="form-label">Nome completo <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?php echo $modoEdicao ? htmlspecialchars($clienteEditar['nome']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Telefone <span class="text-danger">*</span></label>
                    <input type="text" name="telefone" class="form-control" required
                           value="<?php echo $modoEdicao ? htmlspecialchars($clienteEditar['telefone']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" class="form-control"
                           value="<?php echo $modoEdicao ? htmlspecialchars($clienteEditar['cidade']) : 'São Paulo'; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo $modoEdicao ? htmlspecialchars($clienteEditar['email']) : ''; ?>">
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <?php if ($modoEdicao): ?>
                        <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-save me-1"></i><?php echo $modoEdicao ? 'Salvar Alterações' : 'Cadastrar Cliente'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><i class="bi bi-people-fill me-2"></i>Clientes Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Cidade</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhum cliente cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo (int)$cliente['id_cliente']; ?></td>
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['cidade']); ?></td>
                                    <td class="text-center">
                                        <a href="clientes.php?editar=<?php echo (int)$cliente['id_cliente']; ?>"
                                           class="btn btn-sm btn-outline-warning me-1" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="clientes.php?excluir=<?php echo (int)$cliente['id_cliente']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir o cliente <?php echo addslashes(htmlspecialchars($cliente['nome'])); ?>?')">
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
