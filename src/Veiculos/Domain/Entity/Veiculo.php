<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Entity;

use InvalidArgumentException;

final class Veiculo {
    public function __construct(
        private int $id,
        private Placa $placa,
        private string $marca,
        private string $modelo,
    ) {
        if (trim($marca) === '') {
            throw new InvalidArgumentException('Marca não pode ser vazia.');
        }
        if (trim($modelo) === '') {
            throw new InvalidArgumentException('Modelo não pode ser vazio.');
        }
    }

    public function id(): int {
        return $this->id;
    }

    public function placa(): Placa {
        return $this->placa;
    }

    public function marca(): string {
        return $this->marca;
    }

    public function modelo(): string {
        return $this->modelo;
    }

    public function comId(int $id): self {
        $clone = clone $this;
        $clone->id = $id;

        return $clone;
    }

    public function comPlaca(Placa $placa): self {
        $clone = clone $this;
        $clone->placa = $placa;

        return $clone;
    }

    public function comMarca(string $marca): self {
        $clone = clone $this;
        $clone->marca = $marca;

        return $clone;
    }

    public function comModelo(string $modelo): self {
        $clone = clone $this;
        $clone->modelo = $modelo;

        return $clone;
    }
}
