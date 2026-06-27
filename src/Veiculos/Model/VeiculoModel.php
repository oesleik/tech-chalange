<?php

declare(strict_types=1);

namespace App\Veiculos\Model;

class VeiculoModel {
    public function __construct(
        private int $id,
        private string $placa,
        private string $marca,
        private string $modelo,
    ) {
        $this->placa = preg_replace("/(.{3})(.{4})/", "$1-$2", str_replace([" ", "-"], "", $this->placa));
    }

    public function getId(): int {
        return $this->id;
    }

    public function getPlaca(): string {
        return $this->placa;
    }

    public function getMarca(): string {
        return $this->marca;
    }

    public function getModelo(): string {
        return $this->modelo;
    }

    public function withId(int $id): self {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withPlaca(string $placa): self {
        $new = clone $this;
        $new->placa = $placa;
        return $new;
    }

    public function withMarca(string $marca): self {
        $new = clone $this;
        $new->marca = $marca;
        return $new;
    }

    public function withModelo(string $modelo): self {
        $new = clone $this;
        $new->modelo = $modelo;
        return $new;
    }
}
