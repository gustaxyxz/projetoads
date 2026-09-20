USE `metalurgica_oliveira`;

CREATE OR REPLACE VIEW `vw_orcamentos_detalhados` AS
SELECT 
    o.id_orcamento,
    o.data_solicitacao,
    o.status AS status_orcamento,
    c.id_cliente,
    c.nome AS nome_cliente,
    c.telefone AS telefone_cliente,
    c.email AS email_cliente,
    c.cidade AS cidade_cliente,
    oi.id_item,
    p.id_produto,
    p.nome_produto,
    p.categoria AS categoria_produto,
    p.preco_base AS preco_unitario,
    oi.quantidade_solicitada,
    ROUND(oi.quantidade_solicitada * p.preco_base, 2) AS subtotal_item
FROM `orcamentos` o
INNER JOIN `clientes` c ON o.id_cliente = c.id_cliente
INNER JOIN `orcamento_itens` oi ON o.id_orcamento = oi.id_orcamento
INNER JOIN `produtos` p ON oi.id_produto = p.id_produto;

CREATE OR REPLACE VIEW `vw_analise_faturamento_produtos` AS
WITH dados_vendas_brutos AS (
    SELECT 
        p.id_produto,
        p.nome_produto,
        p.categoria,
        p.preco_base,
        p.quantidade_disponivel,
        COUNT(DISTINCT oi.id_orcamento) AS total_pedidos_vinculados,
        COALESCE(SUM(oi.quantidade_solicitada), 0) AS volume_total_demandado,
        COALESCE(SUM(oi.quantidade_solicitada * p.preco_base), 0.00) AS faturamento_bruto_potencial
    FROM `produtos` p
    LEFT JOIN `orcamento_itens` oi ON p.id_produto = oi.id_produto
    LEFT JOIN `orcamentos` o ON oi.id_orcamento = o.id_orcamento
    GROUP BY p.id_produto, p.nome_produto, p.categoria, p.preco_base, p.quantidade_disponivel
)
SELECT 
    id_produto,
    nome_produto,
    categoria,
    preco_base,
    quantidade_disponivel AS estoque_atual,
    total_pedidos_vinculados,
    volume_total_demandado,
    faturamento_bruto_potencial,
    CASE 
        WHEN quantidade_disponivel = 0 THEN 'SEM ESTOQUE'
        WHEN quantidade_disponivel <= 5 THEN 'ESTOQUE CRÍTICO'
        ELSE 'ESTOQUE NORMAL'
    END AS status_estoque
FROM dados_vendas_brutos;
