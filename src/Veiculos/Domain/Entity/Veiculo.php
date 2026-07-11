<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Entity;

use InvalidArgumentException;

final class Veiculo {
    public function __construct(
        private readonly ?int $id,
        private readonly string $placa,
        private readonly string $marca,
        private readonly string $modelo,
    ) {
        if (trim($placa) === '') {
            throw new InvalidArgumentException('Placa não pode ser vazia.');
        }
        if (trim($marca) === '') {
            throw new InvalidArgumentException('Marca não pode ser vazia.');
        }
        if (trim($modelo) === '') {
            throw new InvalidArgumentException('Modelo não pode ser vazio.');
        }
    }

    public function id(): ?int {
        return $this->id;
    }

    public function placa(): string {
        return $this->placa;
    }

    public function marca(): string {
        return $this->marca;
    }

    public function modelo(): string {
        return $this->modelo;
    }

    public function placaNormalizada(): string {
        return strtoupper(str_replace(['-', ' '], '', $this->placa));
    }
}
