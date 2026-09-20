DROP TABLE IF EXISTS `orcamento_itens`;
DROP TABLE IF EXISTS `orcamentos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `produtos`;

CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cliente` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `telefone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `cidade` VARCHAR(50) DEFAULT 'São Paulo',
  `data_cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `produtos` (
  `id_produto` INT NOT NULL AUTO_INCREMENT,
  `nome_produto` VARCHAR(150) NOT NULL,
  `categoria` VARCHAR(50) NOT NULL DEFAULT 'Galpão Metálico',
  `preco_base` DECIMAL(10,2) NOT NULL,
  `quantidade_disponivel` INT NOT NULL,
  `imagem_url` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orcamentos` (
  `id_orcamento` INT NOT NULL AUTO_INCREMENT,
  `id_cliente` INT NOT NULL,
  `data_solicitacao` DATE NOT NULL,
  `status` ENUM('Pendente', 'Aprovado', 'Em Fabricação', 'Entregue', 'Cancelado') DEFAULT 'Pendente',
  PRIMARY KEY (`id_orcamento`),
  KEY `fk_orcamentos_clientes` (`id_cliente`),
  CONSTRAINT `fk_orcamentos_clientes` 
    FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orcamento_itens` (
  `id_item` INT NOT NULL AUTO_INCREMENT,
  `id_orcamento` INT NOT NULL,
  `id_produto` INT NOT NULL,
  `quantidade_solicitada` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `fk_itens_orcamentos` (`id_orcamento`),
  KEY `fk_itens_produtos` (`id_produto`),
  CONSTRAINT `fk_itens_orcamentos` 
    FOREIGN KEY (`id_orcamento`) REFERENCES `orcamentos` (`id_orcamento`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_produtos` 
    FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id_produto`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id_cliente`, `nome`, `telefone`, `email`, `cidade`, `data_cadastro`) VALUES
(1, 'Construtora Horizonte', '(11) 98765-4321', 'compras@horizonte.com.br', 'São Paulo', '2026-01-10 10:00:00'),
(2, 'Agropecuária Vale Verde', '(19) 97123-8899', 'contato@valeverde.agr.br', 'Campinas', '2026-02-15 14:30:00'),
(3, 'Logística & Transportes Express', '(11) 94567-1122', 'suprimentos@expresslog.com.br', 'Guarulhos', '2026-03-01 09:15:00'),
(4, 'Indústria Mecânica Alvorada', '(12) 99888-7766', 'engenharia@alvorada.ind.br', 'São José dos Campos', '2026-03-10 16:45:00'),
(5, 'Engenharia e Obras Santos', '(13) 98111-2233', 'santos@engobras.com.br', 'Santos', '2026-03-12 11:20:00')
ON DUPLICATE KEY UPDATE `id_cliente`=`id_cliente`;

INSERT INTO `produtos` (`id_produto`, `nome_produto`, `categoria`, `preco_base`, `quantidade_disponivel`, `imagem_url`) VALUES
(1, 'Estrutura De Galpão 8x15 120m²', 'Galpões', 20000.00, 3, 'assets/imagens/galpao 120.png'),
(2, 'Estrutura De Galpão 12x20 240m²', 'Galpões', 30000.00, 2, 'assets/imagens/galpao 240.png'),
(3, 'Estrutura De Galpão 15x45 675m²', 'Galpões', 50000.00, 4, 'assets/imagens/galpao 675.png'),
(4, 'Viga I Metálica 6m Laminada', 'Perfis Estruturais', 850.00, 45, NULL),
(5, 'Chapa de Aço Galvanizada #14 1.95mm', 'Chapas', 340.00, 2, NULL),
(6, 'Tubo Retangular Aço Carbono 50x30', 'Tubos e Perfis', 120.00, 4, NULL)
ON DUPLICATE KEY UPDATE `id_produto`=`id_produto`;

INSERT INTO `orcamentos` (`id_orcamento`, `id_cliente`, `data_solicitacao`, `status`) VALUES
(1, 1, '2026-03-01', 'Aprovado'),
(2, 2, '2026-03-03', 'Em Fabricação'),
(3, 3, '2026-03-05', 'Pendente'),
(4, 4, '2026-03-08', 'Pendente'),
(5, 5, '2026-03-10', 'Aprovado'),
(6, 1, '2026-03-12', 'Pendente')
ON DUPLICATE KEY UPDATE `id_orcamento`=`id_orcamento`;

INSERT INTO `orcamento_itens` (`id_item`, `id_orcamento`, `id_produto`, `quantidade_solicitada`) VALUES
(1, 1, 2, 2.00),
(2, 2, 3, 1.00),
(3, 3, 1, 2.00),
(4, 4, 4, 30.00),
(5, 5, 5, 15.00),
(6, 6, 6, 25.00)
ON DUPLICATE KEY UPDATE `id_item`=`id_item`;

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
