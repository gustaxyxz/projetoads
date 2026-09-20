USE `metalurgica_oliveira`;

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_dashboard_indicadores`$$

CREATE PROCEDURE `sp_dashboard_indicadores`(
    IN p_status VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_data_inicio DATE,
    IN p_data_fim DATE,
    IN p_limite INT,
    IN p_pagina INT
)
BEGIN
    DECLARE v_offset INT;

    IF p_limite IS NULL OR p_limite <= 0 THEN
        SET p_limite = 10;
    END IF;

    IF p_pagina IS NULL OR p_pagina <= 0 THEN
        SET p_pagina = 1;
    END IF;

    SET v_offset = (p_pagina - 1) * p_limite;

    SELECT 
        o.id_orcamento,
        c.nome AS cliente_nome,
        c.email AS cliente_email,
        c.cidade AS cliente_cidade,
        o.data_solicitacao,
        o.status AS status_orcamento,
        COUNT(oi.id_item) AS total_itens_orcados,
        COALESCE(SUM(oi.quantidade_solicitada), 0) AS quantidade_pecas_total,
        fn_calcular_total_orcamento(o.id_orcamento) AS valor_total_orcamento
    FROM `orcamentos` o
    INNER JOIN `clientes` c ON o.id_cliente = c.id_cliente
    LEFT JOIN `orcamento_itens` oi ON o.id_orcamento = oi.id_orcamento
    WHERE 
        (p_status IS NULL OR p_status = '' OR p_status = 'Todos' OR o.status = p_status)
        AND (p_data_inicio IS NULL OR o.data_solicitacao >= p_data_inicio)
        AND (p_data_fim IS NULL OR o.data_solicitacao <= p_data_fim)
    GROUP BY 
        o.id_orcamento, 
        c.nome, 
        c.email, 
        c.cidade, 
        o.data_solicitacao, 
        o.status
    ORDER BY o.data_solicitacao DESC, o.id_orcamento DESC
    LIMIT p_limite OFFSET v_offset;

END$$

DELIMITER ;
