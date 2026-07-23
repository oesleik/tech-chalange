<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Domain\Entity;

use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use PHPUnit\Framework\TestCase;

final class LancamentoEstoqueTest extends TestCase
{
    public function testCriarRetornaEntidadeSemId(): void
    {
        $lancamento = LancamentoEstoque::criar(1, 5, TipoLancamentoEnum::ENTRADA);

        $this->assertNull($lancamento->id());
        $this->assertSame(1, $lancamento->pecaId());
        $this->assertSame(5, $lancamento->quantidade());
        $this->assertSame(TipoLancamentoEnum::ENTRADA, $lancamento->tipo());
    }

    public function testReconstituirRetornaEntidadeComId(): void
    {
        $lancamento = LancamentoEstoque::reconstituir(10, 2, 3, TipoLancamentoEnum::BAIXA);

        $this->assertSame(10, $lancamento->id());
        $this->assertSame(2, $lancamento->pecaId());
        $this->assertSame(3, $lancamento->quantidade());
        $this->assertSame(TipoLancamentoEnum::BAIXA, $lancamento->tipo());
    }

    public function testComIdRetornaNovaInstanciaComId(): void
    {
        $original   = LancamentoEstoque::criar(1, 5, TipoLancamentoEnum::ENTRADA);
        $comId      = $original->comId(42);

        // o original não muda — imutabilidade
        $this->assertNull($original->id());
        $this->assertSame(42, $comId->id());
        $this->assertSame(1, $comId->pecaId());
        $this->assertSame(5, $comId->quantidade());
    }

    public function testEnumValores(): void
    {
        $this->assertSame('entrada', TipoLancamentoEnum::ENTRADA->value);
        $this->assertSame('baixa', TipoLancamentoEnum::BAIXA->value);
    }
}