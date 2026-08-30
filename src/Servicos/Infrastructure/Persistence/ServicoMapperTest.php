<?php

declare(strict_types=1);

namespace Tests\Servicos\Infrastructure\Persistence;

use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Infrastructure\Persistence\ServicoMapper;
use PHPUnit\Framework\TestCase;

final class ServicoMapperTest extends TestCase {
    public function testParaEntidadeConverteLinhaEmServico(): void {
        $linha = [
            'id' => '1',
            'descricao' => 'Troca de óleo',
            'valor_unitario' => '49.90',
        ];

        $servico = ServicoMapper::paraEntidade($linha);

        $this->assertInstanceOf(Servico::class, $servico);
        $this->assertSame(1, $servico->id());
        $this->assertSame('Troca de óleo', $servico->descricao());
        $this->assertSame(49.90, $servico->valorUnitario()->getValue());
    }

    public function testParaEntidadeConverteTiposCorretamente(): void {
        $linha = [
            'id' => 10,
            'descricao' => 'Alinhamento',
            'valor_unitario' => 120.0,
        ];

        $servico = ServicoMapper::paraEntidade($linha);

        $this->assertSame(10, $servico->id());
        $this->assertSame(120.0, $servico->valorUnitario()->getValue());
    }
}
