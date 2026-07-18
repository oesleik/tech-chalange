<?php

declare(strict_types=1);

namespace App\Peca\Infrastructure\Persistence;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;
use App\Core\AppDatabase;
use PDO;

final class PecaGateway implements PecaGatewayInterface {
    private const TABELA = 'pecas';

    public function __construct(private readonly AppDatabase $pdo) {}

    public function buscarPorId(int $id): ?Peca {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . self::TABELA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetchObject();
        return $row ? PecaMapper::paraEntidade($row) : null;
    }

    public function inserir(Peca $peca): Peca {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . self::TABELA . ' (descricao, valor_unitario) VALUES (?, ?)'
        );
        $stmt->execute([
            $peca->descricao(),
            $peca->valorUnitario()->getValue(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $peca->comId($id);
    }

    public function atualizar(Peca $peca): Peca {
        $stmt = $this->pdo->prepare(
            'UPDATE ' . self::TABELA . ' SET descricao = ?, valor_unitario = ? WHERE id = ?'
        );
        $stmt->execute([
            $peca->descricao(),
            $peca->valorUnitario()->getValue(),
            $peca->id(),
        ]);

        return $peca;
    }

    public function listar(): array {
        $result = $this->pdo->query('SELECT * FROM ' . self::TABELA, PDO::FETCH_OBJ);

        $pecas = [];
        foreach ($result as $row) {
            $pecas[] = PecaMapper::paraEntidade($row);
        }
        return $pecas;
    }
}