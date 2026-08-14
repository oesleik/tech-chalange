CREATE TABLE `ordens_servico` (
    `id`                 INT UNSIGNED                                                                                    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_cliente`         INT UNSIGNED                                                                                    NOT NULL,
    `id_veiculo`         INT UNSIGNED                                                                                    NOT NULL,
    `situacao`           ENUM('Recebida', 'EmDiagnostico', 'AguardandoAprovacao', 'Aprovada', 'Rejeitada', 'EmExecucao', 'Finalizada', 'Entregue') NOT NULL DEFAULT 'Recebida',
    `valor_total`        DECIMAL(10, 2)                                                                                  NOT NULL,
    `data_solicitacao`   DATETIME                                                                                        NOT NULL,
    `data_aprovacao`     DATETIME,
    CONSTRAINT `fk_ordens_servico_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
    CONSTRAINT `fk_ordens_servico_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id`)
);
