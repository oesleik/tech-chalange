CREATE TABLE `servicos` (
    `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `descricao`      VARCHAR(255)   NOT NULL,
    `valor_unitario` DECIMAL(10, 2) NOT NULL
);
