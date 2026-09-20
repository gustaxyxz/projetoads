<?php include "includes/header.php"; ?>

<main class="container my-5">
    <section class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <small class="text-muted">Total de orçamentos</small>
                    <h3 id="total-orcamentos" class="mt-2 fw-bold">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <small class="text-muted">Faturamento total</small>
                    <h3 id="valor-total" class="mt-2 fw-bold">R$ 0,00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <small class="text-muted">Estoque crítico</small>
                    <h3 id="estoque-critico" class="mt-2 fw-bold">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <small class="text-muted">Produto destaque</small>
                    <h6 id="produto-destaque" class="mt-2 fw-bold">Nenhum dado registrado</h6>
                </div>
            </div>
        </div>
    </section>

    <section class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h4 class="fw-bold mb-3">Indicadores do negócio</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Volume</th>
                            <th>Estoque</th>
                            <th>Faturamento</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-produtos">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nenhum dado registrado</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<script src="dist/dashboard.js"></script>
<?php include "includes/footer.php"; ?>
