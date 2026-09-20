<?php include "includes/header.php"; ?>
<?php include "includes/conexao.php"; ?>

<?php
$sql = "SELECT * FROM produtos WHERE preco_base > 0";
$resultado = $pdo->query($sql);
$listaProdutos = $resultado->fetchAll(PDO::FETCH_ASSOC);

function calcularDescontoPix($precoOriginal, $percentualDesconto) {
    $desconto = $precoOriginal * ($percentualDesconto / 100);
    return $precoOriginal - $desconto;
}

function filtrarProdutosPorPreco(array $produtos, $precoMaximo) {
    $produtosFiltrados = [];
    foreach ($produtos as $produto) {
        if ($produto["preco_base"] <= $precoMaximo) {
            $produtosFiltrados[] = $produto;
        }
    }
    return $produtosFiltrados;
}

function validarCatalogo(array $produtos) {
    if (count($produtos) === 0) {
        return "Atenção: Nosso catálogo está sendo atualizado. Nenhum produto disponível no momento.";
    }
    foreach ($produtos as $produto) {
        if ($produto["preco_base"] < 0) {
            return "Erro de consistência: Foi detectado um produto com valor negativo no banco de dados.";
        }
    }
    return "OK";
}

$mensagemValidacao = validarCatalogo($listaProdutos);
$produtosParaExibir = $listaProdutos;
$filtroPreco = $_GET['max_preco'] ?? '';

if ($filtroPreco !== '' && is_numeric($filtroPreco)) {
    $produtosParaExibir = filtrarProdutosPorPreco($listaProdutos, floatval($filtroPreco));
}
?>

<main class="container mt-5">
    <h1 class="mb-3">Catálogo de Produtos</h1>

    <p class="lead">Conheça nossos produtos a pronta entrega.</p>

    <?php if ($mensagemValidacao !== "OK") { ?>
        <div class="alert alert-warning"><?php echo $mensagemValidacao; ?></div>
    <?php } else { ?>
        <form method="GET" action="servicos.php" class="row g-3 mb-5 bg-light p-3 rounded shadow-sm align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Qual o seu orçamento ideal?</label>
                <input type="number" name="max_preco" class="form-control" placeholder="Ex: 40000" value="<?php echo htmlspecialchars($filtroPreco); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Filtrar</button>
            </div>
            <div class="col-md-2">
                <a href="servicos.php" class="btn btn-outline-secondary w-100">Limpar</a>
            </div>
        </form>

        <div class="row mt-4">
            <?php foreach ($produtosParaExibir as $produto) { ?>
                <?php 
                    $precoComDesconto = calcularDescontoPix($produto["preco_base"], 10); 
                    $nomesImagens = [
                        1 => 'galpao 120.png',
                        2 => 'galpao 240.png',
                        3 => 'galpao 675.png'
                    ];
                    $arquivoImagem = $nomesImagens[$produto['id_produto']] ?? 'sem-imagem.png';
                ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm d-flex flex-column">
                    <img src="assets/imagens/<?php echo $arquivoImagem; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>" 
                         style="height: 320px; object-fit: cover;" 
                         onerror="this.src='https://via.placeholder.com/800x600.png?text=Sem+Imagem'">

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($produto["nome_produto"]); ?></h5>
                        <p class="card-text text-muted mb-1">
                            <i class="bi bi-box"></i> Estoque: <?php echo htmlspecialchars($produto["quantidade_disponivel"]); ?> unidades
                        </p>
                        <hr>
                        <div class="mt-auto"> 
                            <p class="text-decoration-line-through text-muted mb-0" style="font-size: 0.9rem;">
                                Tabela: R$ <?php echo number_format($produto["preco_base"], 2, ",", "."); ?>
                            </p>
                            <p class="fw-bold text-success fs-5 mb-0">
                                R$ <?php echo number_format($precoComDesconto, 2, ",", "."); ?> <small class="text-muted fs-6">no PIX</small>
                            </p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 pt-0">
                        <a href="orcamento.php?produto_id=<?php echo $produto["id_produto"]; ?>" class="btn btn-warning w-100 fw-bold">Solicitar Orçamento</a>
                    </div>
                </div>
            </div>
        <?php } ?>
            
            <?php if (count($produtosParaExibir) === 0) { ?>
                <div class="alert alert-info">Nenhum galpão encontrado dentro desse limite de preço.</div>
            <?php } ?>
    </div>
    <?php } ?>
</main>

<?php include "includes/footer.php"; ?>
