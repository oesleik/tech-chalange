<?php

declare(strict_types=1);

namespace App\Clientes\Contract;

use App\Clientes\Validator\CpfOrCnpj;
use App\Clientes\ValueObject\CnpjValue;
use App\Clientes\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\ValueObject\CpfValue;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;

readonly class ListarClientesFiltros extends AbstractContract {
    public function __construct(
        public ?string $cpf_cnpj = null
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'cpf_cnpj' => [
                new Assert\Optional(
                    new CpfOrCnpj(),
                ),
            ],
        ]);
    }

    public function getCpfCnpj(): CpfValue|CnpjValue|null {
        return $this->cpf_cnpj ? CpfOrCnpjValueFactory::make($this->cpf_cnpj) : null;
    }

}
