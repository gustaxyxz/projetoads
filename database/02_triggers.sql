USE `metalurgica_oliveira`;

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_produtos_validar_valores_positivos_bu`$$

CREATE TRIGGER `trg_produtos_validar_valores_positivos_bu`
BEFORE UPDATE ON `produtos`
FOR EACH ROW
BEGIN
    IF NEW.preco_base < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Regra de Negócio: O preço base do produto deve ser positivo!';
    END IF;

    IF NEW.quantidade_disponivel < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Regra de Negócio: A quantidade disponível em estoque não pode ser negativa!';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_orcamento_itens_validar_quantidade_bu`$$

CREATE TRIGGER `trg_orcamento_itens_validar_quantidade_bu`
BEFORE UPDATE ON `orcamento_itens`
FOR EACH ROW
BEGIN
    IF NEW.quantidade_solicitada <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Regra de Negócio: A quantidade solicitada no orçamento deve ser maior que zero!';
    END IF;
END$$

DELIMITER ;
