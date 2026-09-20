CREATE DATABASE IF NOT EXISTS `metalurgica_oliveira` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `metalurgica_oliveira`;

DROP TABLE IF EXISTS `orcamento_itens`;
DROP TABLE IF EXISTS `orcamentos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `produtos`;

CREATE TABLE `clientes` (
  `id_cliente` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `telefone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `cidade` VARCHAR(50) DEFAULT 'São Paulo',
  `data_cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `produtos` (
  `id_produto` INT NOT NULL AUTO_INCREMENT,
  `nome_produto` VARCHAR(150) NOT NULL,
  `categoria` VARCHAR(50) NOT NULL DEFAULT 'Galpão Metálico',
  `preco_base` DECIMAL(10,2) NOT NULL,
  `quantidade_disponivel` INT NOT NULL,
  `imagem_url` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orcamentos` (
  `id_orcamento` INT NOT NULL AUTO_INCREMENT,
  `id_cliente` INT NOT NULL,
  `data_solicitacao` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Pendente',
  PRIMARY KEY (`id_orcamento`),
  KEY `fk_orcamentos_clientes` (`id_cliente`),
  CONSTRAINT `fk_orcamentos_clientes` 
    FOREIGN KEY (`id_cliente`) 
    REFERENCES `clientes` (`id_cliente`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orcamento_itens` (
  `id_item` INT NOT NULL AUTO_INCREMENT,
  `id_orcamento` INT NOT NULL,
  `id_produto` INT NOT NULL,
  `quantidade_solicitada` INT NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `fk_itens_orcamentos` (`id_orcamento`),
  KEY `fk_itens_produtos` (`id_produto`),
  CONSTRAINT `fk_itens_orcamentos` 
    FOREIGN KEY (`id_orcamento`) 
    REFERENCES `orcamentos` (`id_orcamento`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_produtos` 
    FOREIGN KEY (`id_produto`) 
    REFERENCES `produtos` (`id_produto`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id_cliente`, `nome`, `telefone`, `email`, `cidade`) VALUES
(1, 'Construtora Horizonte Ltda', '(11) 98765-4321', 'compras@horizonte.com.br', 'Campinas'),
(2, 'Galpões Brasil Logística', '(11) 97123-1122', 'contato@galpoesbrasil.com.br', 'Jundiaí'),
(3, 'Serralheria e Arte Moderna', '(19) 99345-8877', 'artemoderna@gmail.com', 'Sorocaba'),
(4, 'Indústria Metal-Sul S.A.', '(11) 96543-9900', 'suprimentos@metalsul.com.br', 'São Paulo'),
(5, 'Agropecuária Vale Verde', '(14) 98111-2233', 'fazendas@valeverde.com.br', 'Ribeirão Preto');

INSERT INTO `produtos` (`id_produto`, `nome_produto`, `categoria`, `preco_base`, `quantidade_disponivel`, `imagem_url`) VALUES
(1, 'Estrutura De Galpão 8x15 120m²', 'Galpões', 20000.00, 3, 'galpao 120.png'),
(2, 'Estrutura De Galpão 12x20 240m²', 'Galpões', 30000.00, 2, 'galpao 240.png'),
(3, 'Estrutura De Galpão 15x45 675m²', 'Galpões', 50000.00, 4, 'galpao 675.png'),
(4, 'Viga I Metálica 6m Laminada', 'Perfis Estruturais', 850.00, 45, NULL),
(5, 'Tubo Retangular Aço Carbono 50x30', 'Tubos e Perfis', 120.00, 4, NULL),
(6, 'Chapa de Aço Galvanizada #14 1.95mm', 'Chapas', 340.00, 2, NULL);

INSERT INTO `orcamentos` (`id_orcamento`, `id_cliente`, `data_solicitacao`, `status`) VALUES
(1, 1, '2026-08-10', 'Aprovado'),
(2, 2, '2026-08-18', 'Aprovado'),
(3, 3, '2026-08-25', 'Em Fabricação'),
(4, 4, '2026-09-02', 'Aprovado'),
(5, 5, '2026-09-05', 'Pendente'),
(6, 1, '2026-09-07', 'Pendente');

INSERT INTO `orcamento_itens` (`id_item`, `id_orcamento`, `id_produto`, `quantidade_solicitada`) VALUES
(1, 1, 1, 1),
(2, 1, 4, 10),
(3, 2, 2, 2),
(4, 3, 5, 25),
(5, 4, 3, 1),
(6, 4, 4, 20),
(7, 5, 1, 1),
(8, 6, 6, 15);
