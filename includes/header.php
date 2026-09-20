<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Metalúrgica Oliveira</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <?php
            $pasta = basename(dirname($_SERVER['PHP_SELF']));
            $raiz  = ($pasta === 'admin') ? '../' : '';
        ?>
        <link href="<?php echo $raiz; ?>assets/css/style.css" rel="stylesheet">
    </head>
    <body>
    <?php $paginaAtual = basename($_SERVER['PHP_SELF']); ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-black">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $raiz; ?>index.php">
            <img src="<?php echo $raiz; ?>assets/imagens/Firefly_Gemini Flash_só logo sem fundo e algo minimalista  tipo as inicias m o sera para uma metalurica  807414.png"
                 alt="Metalúrgica Oliveira" height="35" class="rounded-circle" style="filter: brightness(0) invert(1);">
        </a>

        <div class="navbar-nav mx-auto gap-3 fs-10">
            <a class="nav-link d-flex flex-column align-items-center<?php echo $paginaAtual === 'index.php' ? ' active' : ''; ?>" href="<?php echo $raiz; ?>index.php">
                <i class="bi bi-house-door mb-1"></i>
                Início
            </a>
            <a class="nav-link d-flex flex-column align-items-center<?php echo $paginaAtual === 'servicos.php' ? ' active' : ''; ?>" href="<?php echo $raiz; ?>servicos.php">
                <i class="bi bi-box-seam mb-1"></i>
                Produtos
            </a>
            <a class="nav-link d-flex flex-column align-items-center<?php echo $paginaAtual === 'orcamento.php' ? ' active' : ''; ?>" href="<?php echo $raiz; ?>orcamento.php">
                <i class="bi bi-calculator mb-1"></i>
                Orçamento
            </a>
            <a class="nav-link d-flex flex-column align-items-center<?php echo $paginaAtual === 'contato.php' ? ' active' : ''; ?>" href="<?php echo $raiz; ?>contato.php">
                <i class="bi bi-telephone mb-1"></i>
                Contato
            </a>
            <a class="nav-link d-flex flex-column align-items-center<?php echo in_array($paginaAtual, ['clientes.php', 'produtos.php', 'orcamentos.php'], true) ? ' active' : ''; ?>" href="<?php echo $raiz; ?>admin/clientes.php">
                <i class="bi bi-gear mb-1"></i>
                Administração
            </a>
        </div>
    </div>
</nav>