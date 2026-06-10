<?php

declare(strict_types=1);

namespace App\Clientes;

use App\Clientes\Model\ClienteModel;
use App\Clientes\ValueObject\CpfOrCnpjFactory;
use App\Clientes\ValueObject\Email;
use App\Clientes\ValueObject\Telefone;
use App\Core\AppDatabase;
use PDO;

class Persistence {
	public function __construct(
		private AppDatabase $pdo
	) {
	}

	/** @return ClienteModel[] */
    public function listarClientes(): array {
		$result = $this->pdo->query("SELECT * FROM clientes", PDO::FETCH_OBJ);
		$clientes = [];

		foreach ($result as $row) {
			$clientes[] = new ClienteModel(
				id: $row->id,
				nome: $row->nome,
				cpfCnpj: CpfOrCnpjFactory::make($row->cpf_cnpj),
				email: new Email($row->email),
				telefone: new Telefone($row->telefone),
			);
		}

		return $clientes;
    }
}
