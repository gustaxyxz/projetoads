"use strict";
const formatCurrency = (value) => new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
}).format(Number(value) || 0);
const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
};
async function buscarDadosDashboard() {
    const resposta = await fetch('api/dashboard.php', {
        headers: { Accept: 'application/json' }
    });
    if (!resposta.ok) {
        throw new Error('Falha na requisicao da API');
    }
    return await resposta.json();
}
function renderizarIndicadores(dados, produtos) {
    const totalFaturamento = produtos.reduce((acc, item) => acc + Number(item.faturamento_bruto_potencial || 0), 0);
    const estoqueCriticoCount = produtos.filter((item) => item.status_estoque === 'ESTOQUE CRÍTICO').length;
    const produtosOrdenados = [...produtos].sort((a, b) => Number(b.faturamento_bruto_potencial || 0) - Number(a.faturamento_bruto_potencial || 0));
    const produtoDestaque = produtosOrdenados[0] ?? null;
    setText('total-orcamentos', String(dados.total_orcamentos ?? 0));
    setText('valor-total', formatCurrency(dados.valor_total ?? totalFaturamento));
    setText('estoque-critico', String(dados.estoque_critico ?? estoqueCriticoCount));
    setText('produto-destaque', produtoDestaque ? produtoDestaque.nome_produto : 'Nenhum dado registrado');
}
function renderizarTabelaProdutos(produtos) {
    const tabela = document.getElementById('tabela-produtos');
    if (!tabela)
        return;
    if (produtos.length === 0) {
        tabela.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum dado registrado</td></tr>';
        return;
    }
    tabela.innerHTML = produtos.map((produto) => `
        <tr>
            <td>${produto.nome_produto}</td>
            <td>${produto.categoria}</td>
            <td>${produto.volume_total_demandado}</td>
            <td>${produto.estoque_atual}</td>
            <td>${formatCurrency(produto.faturamento_bruto_potencial)}</td>
        </tr>
    `).join('');
}
function renderizarErro() {
    setText('total-orcamentos', '0');
    setText('valor-total', formatCurrency(0));
    setText('estoque-critico', '0');
    setText('produto-destaque', 'Nenhum dado registrado');
    const tabela = document.getElementById('tabela-produtos');
    if (tabela) {
        tabela.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum dado registrado</td></tr>';
    }
}
async function inicializarDashboard() {
    try {
        const dados = await buscarDadosDashboard();
        const produtos = Array.isArray(dados.produtos) ? dados.produtos : [];
        renderizarIndicadores(dados, produtos);
        renderizarTabelaProdutos(produtos);
    }
    catch {
        renderizarErro();
    }
}
document.addEventListener('DOMContentLoaded', inicializarDashboard);
