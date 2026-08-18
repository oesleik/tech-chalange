<?php

declare(strict_types=1);

namespace App\Clientes\Domain\Entity;

use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use InvalidArgumentException;

final class Cliente {
    public function __construct(
        private ?int $id,
        private string $nome,
        private Cpf|Cnpj $cpfCnpj,
        private Email $email,
        private Telefone $telefone,
    ) {
        $this->nome = trim($this->nome);

        if ($this->nome === '') {
            throw new InvalidArgumentException('Nome é obrigatório.');
        }
    }

    public static function criar(
        string $nome,
        Cpf|Cnpj $cpfCnpj,
        Email $email,
        Telefone $telefone,
    ): self {
        return new self(null, $nome, $cpfCnpj, $email, $telefone);
    }

    public static function reconstituir(
        int $id,
        string $nome,
        Cpf|Cnpj $cpfCnpj,
        Email $email,
        Telefone $telefone,
    ): self {
        return new self($id, $nome, $cpfCnpj, $email, $telefone);
    }

    public function id(): ?int {
        return $this->id;
    }

    public function nome(): string {
        return $this->nome;
    }

    public function cpfCnpj(): Cpf|Cnpj {
        return $this->cpfCnpj;
    }

    public function email(): Email {
        return $this->email;
    }

    public function telefone(): Telefone {
        return $this->telefone;
    }

    public function comId(int $id): self {
        return new self($id, $this->nome, $this->cpfCnpj, $this->email, $this->telefone);
    }

    public function comNome(string $nome): self {
        return new self($this->id, $nome, $this->cpfCnpj, $this->email, $this->telefone);
    }

    public function comCpfCnpj(Cpf|Cnpj $cpfCnpj): self {
        return new self($this->id, $this->nome, $cpfCnpj, $this->email, $this->telefone);
    }

    public function comEmail(Email $email): self {
        return new self($this->id, $this->nome, $this->cpfCnpj, $email, $this->telefone);
    }

    public function comTelefone(Telefone $telefone): self {
        return new self($this->id, $this->nome, $this->cpfCnpj, $this->email, $telefone);
    }
}
