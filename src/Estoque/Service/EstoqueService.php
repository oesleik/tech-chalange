<?php

declare(strict_types=1);

namespace App\Estoque\Service;

use App\Core\AppDatabase;
use App\Estoque\Model\EstoquePecaModel;
use App\Estoque\Model\LancamentoEstoqueModel;
use App\Estoque\Model\TipoLancamentoEstoqueEnum;

class EstoqueService {
    public function __construct(private readonly AppDatabase $db) {}

    public function registrarEntrada(int $idPeca, int $quantidade): LancamentoEstoqueModel {
        $this->buscarPecaOuFalha($idPeca);
        $this->inserirLancamento($idPeca, $quantidade, 'entrada');

        return new LancamentoEstoqueModel(
            id: intval($this->db->lastInsertId()),
            idPeca: $idPeca,
            quantidade: $quantidade,
            tipoLancamento: TipoLancamentoEstoqueEnum::ENTRADA,
        );
    }

    public function registrarBaixa(int $idPeca, int $quantidade): LancamentoEstoqueModel {
        $this->buscarPecaOuFalha($idPeca);
        $estoqueAtual = $this->calcularEstoqueAtual($idPeca);

        if ($quantidade > $estoqueAtual) {
            throw new EstoqueInsuficienteException();
        }

        $this->inserirLancamento($idPeca, $quantidade, 'baixa');

        return new LancamentoEstoqueModel(
            id: intval($this->db->lastInsertId()),
            idPeca: $idPeca,
            quantidade: $quantidade,
            tipoLancamento: TipoLancamentoEstoqueEnum::BAIXA,
        );
    }

    public function consultarEstoquePorPeca(int $idPeca): EstoquePecaModel {
        $this->buscarPecaOuFalha($idPeca);

        return new EstoquePecaModel(
            idPeca: $idPeca,
            estoqueAtual: $this->calcularEstoqueAtual($idPeca),
        );
    }

    private function buscarPecaOuFalha(int $id_peca, bool $comValorUnitario = false): void {
        $campos = $comValorUnitario ? 'id, descricao, valor_unitario' : 'id, descricao';

        $stmt = $this->db->prepare("SELECT {$campos} FROM pecas WHERE id = :id");
        $stmt->execute(['id' => $id_peca]);
        $peca = $stmt->fetch();

        if (!$peca) {
            throw new PecaNaoEncontradaException();
        }
    }

    private function calcularEstoqueAtual(int $idPeca): int {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo_lancamento = 'entrada' THEN quantidade ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN tipo_lancamento = 'baixa'  THEN quantidade ELSE 0 END), 0)
                AS estoque_atual
             FROM estoque
             WHERE id_peca = ?"
        );
        $stmt->execute([$idPeca]);
        $resultado = $stmt->fetch();

        return (int) $resultado['estoque_atual'];
    }

    private function inserirLancamento(int $idPeca, int $quantidade, string $tipo): void {
        $stmt = $this->db->prepare(
            'INSERT INTO estoque (id_peca, quantidade, tipo_lancamento)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $idPeca,
            $quantidade,
            $tipo,
        ]);
    }
}
