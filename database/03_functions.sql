USE `metalurgica_oliveira`;

DELIMITER $$

DROP FUNCTION IF EXISTS `fn_calcular_total_orcamento`$$

CREATE FUNCTION `fn_calcular_total_orcamento`(p_id_orcamento INT)
RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0.00;

    SELECT COALESCE(SUM(oi.quantidade_solicitada * p.preco_base), 0.00)
    INTO v_total
    FROM `orcamento_itens` oi
    INNER JOIN `produtos` p ON p.id_produto = oi.id_produto
    WHERE oi.id_orcamento = p_id_orcamento;

    RETURN v_total;
END$$

DELIMITER ;
