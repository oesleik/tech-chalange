<?php

declare(strict_types=1);

namespace App\Clientes\Model;

use App\Clientes\ValueObject\CnpjValue;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;

class ClienteModel {
    public function __construct(
        private int $id,
        private string $nome,
        private CpfValue|CnpjValue $cpfCnpj,
        private EmailValue $email,
        private TelefoneValue $telefone,
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function getCpfCnpj(): CpfValue|CnpjValue {
        return $this->cpfCnpj;
    }

    public function getEmail(): EmailValue {
        return $this->email;
    }

    public function getTelefone(): TelefoneValue {
        return $this->telefone;
    }

    public function withId(int $id): self {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withNome(string $nome): self {
        $new = clone $this;
        $new->nome = $nome;
        return $new;
    }

    public function withCpfCnpj(CpfValue|CnpjValue $cpfCnpj): self {
        $new = clone $this;
        $new->cpfCnpj = $cpfCnpj;
        return $new;
    }

    public function withEmail(EmailValue $email): self {
        $new = clone $this;
        $new->email = $email;
        return $new;
    }

    public function withTelefone(TelefoneValue $telefone): self {
        $new = clone $this;
        $new->telefone = $telefone;
        return $new;
    }
}
