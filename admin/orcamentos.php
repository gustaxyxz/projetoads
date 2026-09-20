<?php
include '../includes/conexao.php';
include '../includes/header.php';

$mensagemSucesso = '';
$mensagemErro    = '';

if (isset($_GET['excluir'])) {
    $idExcluir = (int) $_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_orcamento = :id");
        $stmt->execute([':id' => $idExcluir]);
        $mensagemSucesso = "Orçamento #{$idExcluir} excluído com sucesso! Os itens vinculados foram removidos automaticamente.";
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $mensagemErro = "Não é possível excluir este orçamento pois ele está vinculado a outros registros do sistema.";
        } else {
            $mensagemErro = "Erro ao excluir orçamento: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_status'])) {
    $idOrcamento   = (int) ($_POST['id_orcamento'] ?? 0);
    $novoStatus    = trim($_POST['status'] ?? '');
    $statusValidos = ['Pendente', 'Aprovado', 'Em Fabricação', 'Entregue', 'Cancelado'];

    if ($idOrcamento <= 0 || !in_array($novoStatus, $statusValidos, true)) {
        $mensagemErro = "Dados inválidos para alterar o status.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE orcamentos SET status = :status WHERE id_orcamento = :id");
            $stmt->execute([':status' => $novoStatus, ':id' => $idOrcamento]);
            $mensagemSucesso = "Status do Orçamento #{$idOrcamento} atualizado para \"{$novoStatus}\" com sucesso!";
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar status: " . $e->getMessage();
        }
    }
}

$orcamentos = $pdo->query(
    "SELECT
        o.id_orcamento,
        o.data_solicitacao,
        o.status,
        c.nome AS nome_cliente,
        c.email AS email_cliente,
        fn_calcular_total_orcamento(o.id_orcamento) AS valor_total,
        COUNT(oi.id_item) AS total_itens
     FROM orcamentos o
     INNER JOIN clientes c ON o.id_cliente = c.id_cliente
     LEFT JOIN orcamento_itens oi ON o.id_orcamento = oi.id_orcamento
     GROUP BY o.id_orcamento, o.data_solicitacao, o.status, c.nome, c.email
     ORDER BY o.data_solicitacao DESC, o.id_orcamento DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$statusDisponiveis = ['Pendente', 'Aprovado', 'Em Fabricação', 'Entregue', 'Cancelado'];
$badgesStatus = [
    'Pendente'      => 'bg-secondary',
    'Aprovado'      => 'bg-success',
    'Em Fabricação' => 'bg-warning text-dark',
    'Entregue'      => 'bg-primary',
    'Cancelado'     => 'bg-danger',
];
?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gerenciar Orçamentos</h1>
        <a href="produtos.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left-circle me-1"></i>Ir para Produtos</a>
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

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Orçamentos Registrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Itens</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orcamentos) === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">Nenhum orçamento registrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orcamentos as $orc): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$orc['id_orcamento']; ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($orc['nome_cliente']); ?>
                                        <small class="d-block text-muted"><?php echo htmlspecialchars($orc['email_cliente']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($orc['data_solicitacao'])); ?></td>
                                    <td><?php echo (int)$orc['total_itens']; ?> item(s)</td>
                                    <td><strong>R$ <?php echo number_format((float)$orc['valor_total'], 2, ',', '.'); ?></strong></td>
                                    <td>
                                        <?php
                                            $statusAtual = $orc['status'];
                                            $badgeClass  = $badgesStatus[$statusAtual] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($statusAtual); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning me-1"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#editar-<?php echo (int)$orc['id_orcamento']; ?>"
                                                title="Alterar status">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="orcamentos.php?excluir=<?php echo (int)$orc['id_orcamento']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Excluir orçamento"
                                           onclick="return confirm('Excluir o Orçamento #<?php echo (int)$orc['id_orcamento']; ?>? Todos os itens vinculados serão removidos.')">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editar-<?php echo (int)$orc['id_orcamento']; ?>">
                                    <td colspan="7" class="bg-light py-3 px-4">
                                        <form method="POST" action="orcamentos.php" class="d-flex align-items-center gap-3">
                                            <input type="hidden" name="id_orcamento" value="<?php echo (int)$orc['id_orcamento']; ?>">
                                            <label class="form-label mb-0 fw-bold">Novo status:</label>
                                            <select name="status" class="form-select form-select-sm w-auto">
                                                <?php foreach ($statusDisponiveis as $s): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo $s === $statusAtual ? 'selected' : ''; ?>>
                                                        <?php echo $s; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="alterar_status" class="btn btn-sm btn-dark">
                                                <i class="bi bi-save me-1"></i>Salvar
                                            </button>
                                        </form>
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
