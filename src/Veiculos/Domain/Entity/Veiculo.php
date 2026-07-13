<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Entity;

use InvalidArgumentException;

final class Veiculo {
    public function __construct(
        private readonly ?int $id,
        private readonly Placa $placa,
        private readonly string $marca,
        private readonly string $modelo,
    ) {
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
        return new self($id, $this->placa, $this->marca, $this->modelo);
    }

    public function comPlaca(Placa $placa): self {
        return new self($this->id, $placa, $this->marca, $this->modelo);
    }

    public function comMarca(string $marca): self {
        return new self($this->id, $this->placa, $marca, $this->modelo);
    }

    public function comModelo(string $modelo): self {
        return new self($this->id, $this->placa, $this->marca, $modelo);
    }
}
