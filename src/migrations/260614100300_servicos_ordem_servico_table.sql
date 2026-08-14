CREATE TABLE `servicos_ordem_servico` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_ordem_servico`   INT UNSIGNED NOT NULL,
    `id_servico`         INT UNSIGNED NOT NULL,
    `quantidade`         INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_servicos_ordem_servico_ordem` FOREIGN KEY (`id_ordem_servico`) REFERENCES `ordens_servico` (`id`),
    CONSTRAINT `fk_servicos_ordem_servico_servico` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id`)
);
