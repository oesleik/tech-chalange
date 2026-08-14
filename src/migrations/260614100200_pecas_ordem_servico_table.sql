CREATE TABLE `pecas_ordem_servico` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_ordem_servico`   INT UNSIGNED NOT NULL,
    `id_peca`            INT UNSIGNED NOT NULL,
    `quantidade`         INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_pecas_ordem_servico_ordem` FOREIGN KEY (`id_ordem_servico`) REFERENCES `ordens_servico` (`id`),
    CONSTRAINT `fk_pecas_ordem_servico_peca` FOREIGN KEY (`id_peca`) REFERENCES `pecas` (`id`)
);
