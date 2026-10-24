ALTER TABLE `ordens_servico` ADD COLUMN `data_situacao` DATETIME NULL AFTER `data_aprovacao`;

-- Não há histórico de quando a situação atual começou. A melhor aproximação é a aprovação, quando existe, ou a solicitação.
UPDATE `ordens_servico` SET `data_situacao` = COALESCE(`data_aprovacao`, `data_solicitacao`);

ALTER TABLE `ordens_servico` MODIFY COLUMN `data_situacao` DATETIME NOT NULL;
