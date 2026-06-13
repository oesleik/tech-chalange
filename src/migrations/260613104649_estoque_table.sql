CREATE TABLE `estoque` (
    `id`              INT UNSIGNED             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_peca`         INT UNSIGNED             NOT NULL,
    `quantidade`      INT UNSIGNED             NOT NULL,
    `tipo_lancamento` ENUM('entrada', 'baixa') NOT NULL,
    CONSTRAINT `fk_estoque_peca` FOREIGN KEY (`id_peca`) REFERENCES `pecas` (`id`)
);