-- OS 1
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao)
VALUES
(2, 3, 'EmDiagnostico', 322.90, '2026-06-01 08:30:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 2, 2),
(@id_os, 6, 1);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 2, 1),
(@id_os, 6, 1);

-- OS 2
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao, data_aprovacao)
VALUES
(3, 5, 'Aprovada', 605.00, '2026-06-03 09:00:00', '2026-06-04 14:00:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 4, 1),
(@id_os, 10, 2);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 5, 1),
(@id_os, 15, 1);

-- OS 3
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao, data_aprovacao)
VALUES
(6, 11, 'EmExecucao', 601.00, '2026-06-05 10:15:00', '2026-06-06 11:00:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 3, 4),
(@id_os, 9, 1);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 1, 1),
(@id_os, 8, 1);

-- OS 4
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao, data_aprovacao)
VALUES
(8, 14, 'Finalizada', 850.00, '2026-06-08 07:45:00', '2026-06-09 08:30:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 5, 1),
(@id_os, 13, 1);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 7, 1),
(@id_os, 3, 1);

-- OS 5
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao, data_aprovacao)
VALUES
(10, 16, 'Entregue', 554.80, '2026-06-10 13:00:00', '2026-06-11 09:00:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 11, 2),
(@id_os, 14, 1);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 14, 1),
(@id_os, 9, 1);

-- OS 6
INSERT INTO ordens_servico
(id_cliente, id_veiculo, situacao, valor_total, data_solicitacao)
VALUES
(9, 10, 'AguardandoAprovacao', 729.00, '2026-06-15 11:30:00');

SET @id_os = LAST_INSERT_ID();

INSERT INTO pecas_ordem_servico
(id_ordem_servico, id_peca, quantidade)
VALUES
(@id_os, 15, 1),
(@id_os, 7, 2);

INSERT INTO servicos_ordem_servico
(id_ordem_servico, id_servico, quantidade)
VALUES
(@id_os, 11, 1),
(@id_os, 4, 1);