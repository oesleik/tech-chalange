<?php

declare(strict_types=1);

namespace App\Clientes\Contract;

use App\Clientes\Model\ClienteModel;
use App\Core\Contract\AbstractContract;
use JsonSerializable;
use Override;

readonly class ListarClientesResponse extends AbstractContract implements JsonSerializable {

	/** @param ClienteModel[] */
	public function __construct(
		public array $clientes
	) {
	}

	public function jsonSerialize(): mixed
	{
		return [
			"clientes" => array_map(function (ClienteModel $cliente) {
				return [
					"id" => $cliente->getId(),
					"nome" => $cliente->getNome(),
					"cpf_cnpj" => $cliente->getCpfCnpj()->getMaskedValue(),
					"email" => $cliente->getEmail()->getMaskedValue(),
					"telefone" => $cliente->getTelefone()->getMaskedValue(),
				];
			}, $this->clientes)
		];
	}
    
}
