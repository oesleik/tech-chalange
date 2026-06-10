<?php

declare(strict_types=1);

namespace App\Clientes\Model;

use App\Clientes\ValueObject\Cnpj;
use App\Clientes\ValueObject\Cpf;
use App\Clientes\ValueObject\Email;
use App\Clientes\ValueObject\Telefone;

class ClienteModel {
	public function __construct(
		private int $id,
		private string $nome,
		private Cpf|Cnpj $cpfCnpj,
		private Email $email,
		private Telefone $telefone,
	) {
	}

	public function getId(): int {
		return $this->id;
	}

	public function getNome(): string {
		return $this->nome;
	}

	public function getCpfCnpj(): Cpf|Cnpj {
		return $this->cpfCnpj;
	}

	public function getEmail(): Email {
		return $this->email;
	}

	public function getTelefone(): Telefone {
		return $this->telefone;
	}
}
