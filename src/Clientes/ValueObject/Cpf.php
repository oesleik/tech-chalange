<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

class Cpf {
	public function __construct(
		private string $cpf
	) {
		$this->cpf = str_replace(['-', '.'], '', $cpf);
	}

	public function getValue(): string {
		return $this->cpf;
	}

	public function getFormattedValue(): string {
		return preg_replace("/(.{3})(.{3})(.{3})(.{2})/", "$1.$2.$3-$4", $this->cpf) ?: $this->cpf;
	}

	public function getMaskedValue(): string {
		if (strlen($this->cpf) != 11) {
			return preg_replace('/./', '*', $this->cpf);
		}

		return preg_replace("/(.{2})(.{1})(.{3})(.{3})(.{2})/", "$1*.***.***-$5", $this->cpf) ?: "***.***.***-**";
	}

	public function __toString() {
		return $this->getValue();
	}
}
