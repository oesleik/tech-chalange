<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\DTO;

use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueInputDTO;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueInputDTO;
use App\Estoque\Presentation\Http\DTO\RegistrarBaixaEstoqueMapper;
use App\Estoque\Presentation\Http\DTO\RegistrarEntradaEstoqueMapper;
use PHPUnit\Framework\TestCase;

final class RegistrarEstoqueMapperTest extends TestCase {
    public function testMapperEntradaCriaInputDTO(): void {
        $resultado = RegistrarEntradaEstoqueMapper::parse(['id_peca' => 7, 'quantidade' => 12]);

        $this->assertInstanceOf(RegistrarEntradaEstoqueInputDTO::class, $resultado);
        $this->assertSame(7, $resultado->pecaId);
        $this->assertSame(12, $resultado->quantidade);
    }

    public function testMapperBaixaCriaInputDTO(): void {
        $resultado = RegistrarBaixaEstoqueMapper::parse(['id_peca' => 7, 'quantidade' => 3]);

        $this->assertInstanceOf(RegistrarBaixaEstoqueInputDTO::class, $resultado);
        $this->assertSame(7, $resultado->pecaId);
        $this->assertSame(3, $resultado->quantidade);
    }

    /** @dataProvider dadosInvalidos */
    public function testMapperEntradaRejeitaDadosInvalidos(array $dados, string $mensagem): void {
        $this->expectExceptionMessage($mensagem);
        RegistrarEntradaEstoqueMapper::parse($dados);
    }

    /** @dataProvider dadosInvalidos */
    public function testMapperBaixaRejeitaDadosInvalidos(array $dados, string $mensagem): void {
        $this->expectExceptionMessage($mensagem);
        RegistrarBaixaEstoqueMapper::parse($dados);
    }

    public static function dadosInvalidos(): array {
        return [
            'sem id' => [[], 'O campo id_peca deve ser um inteiro positivo.'],
            'id zero' => [['id_peca' => 0, 'quantidade' => 1], 'O campo id_peca deve ser um inteiro positivo.'],
            'id negativo' => [['id_peca' => -1, 'quantidade' => 1], 'O campo id_peca deve ser um inteiro positivo.'],
            'id string' => [['id_peca' => '1', 'quantidade' => 1], 'O campo id_peca deve ser um inteiro positivo.'],
            'sem quantidade' => [['id_peca' => 1], 'O campo quantidade deve ser um inteiro positivo.'],
            'quantidade zero' => [['id_peca' => 1, 'quantidade' => 0], 'O campo quantidade deve ser um inteiro positivo.'],
            'quantidade negativa' => [['id_peca' => 1, 'quantidade' => -1], 'O campo quantidade deve ser um inteiro positivo.'],
            'quantidade string' => [['id_peca' => 1, 'quantidade' => '5'], 'O campo quantidade deve ser um inteiro positivo.'],
        ];
    }
}
