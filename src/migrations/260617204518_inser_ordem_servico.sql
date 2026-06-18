INSERT INTO `ordens_servico`
            (`id_cliente`,
             `id_veiculo`,
             `situacao`,
             `valor_total`,
             `data_solicitacao`)
VALUES      (1,
             1,
             'Recebida',
             594.80,
             Now());

INSERT INTO `pecas_ordem_servico`
            (`id_ordem_servico`,
             `id_peca`,
             `quantidade`)
VALUES      (Last_insert_id(),
             1,
             2),
            (Last_insert_id(),
             4,
             1);

INSERT INTO `servicos_ordem_servico`
            (`id_ordem_servico`,
             `id_servico`,
             `quantidade`)
VALUES      (Last_insert_id(),
             1,
             1),
            (Last_insert_id(),
             3,
             1);  