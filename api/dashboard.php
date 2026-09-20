<?php
include '../includes/conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $produtos = $pdo->query("SELECT * FROM vw_analise_faturamento_produtos ORDER BY faturamento_bruto_potencial DESC")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("CALL sp_dashboard_indicadores(:status, :data_inicio, :data_fim, :limite, :pagina)");
    $stmt->execute([
        ':status'      => null,
        ':data_inicio' => null,
        ':data_fim'    => null,
        ':limite'      => 100,
        ':pagina'      => 1,
    ]);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $valorTotal = array_reduce($produtos, function ($total, $produto) {
        return $total + (float) ($produto['faturamento_bruto_potencial'] ?? 0);
    }, 0.0);

    $estoqueCritico = array_values(array_filter($produtos, function ($produto) {
        return ($produto['status_estoque'] ?? '') === 'ESTOQUE CRÍTICO';
    }));

    $produtoMaisVendido = 'Nenhum dado registrado';
    if (!empty($produtos)) {
        $produtoMaisVendido = $produtos[0]['nome_produto'] ?? 'Nenhum dado registrado';
    }

    $dados = [
        'total_orcamentos'     => count($orcamentos),
        'valor_total'          => round($valorTotal, 2),
        'estoque_critico'      => count($estoqueCritico),
        'produto_mais_vendido' => $produtoMaisVendido,
        'produtos'             => array_map(function ($produto) {
            return [
                'nome_produto'               => $produto['nome_produto'] ?? 'Produto sem nome',
                'categoria'                  => $produto['categoria'] ?? 'Sem categoria',
                'faturamento_bruto_potencial' => (float) ($produto['faturamento_bruto_potencial'] ?? 0),
                'estoque_atual'              => (int) ($produto['estoque_atual'] ?? 0),
                'status_estoque'             => $produto['status_estoque'] ?? 'SEM DADO',
                'volume_total_demandado'     => (int) ($produto['volume_total_demandado'] ?? 0),
            ];
        }, $produtos),
    ];

    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $erro) {
    http_response_code(500);
    echo json_encode([
        'mensagem'             => 'Nenhum dado registrado no momento.',
        'erro'                 => $erro->getMessage(),
        'total_orcamentos'     => 0,
        'valor_total'          => 0,
        'estoque_critico'      => 0,
        'produto_mais_vendido' => 'Nenhum dado registrado',
        'produtos'             => [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
