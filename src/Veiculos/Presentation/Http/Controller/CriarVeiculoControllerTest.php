<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\CriarVeiculo\CriarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Presentation\Http\Controller\CriarVeiculoController;
use App\Veiculos\Presentation\Http\DTO\CriarVeiculoMapper;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarVeiculoControllerTest extends TestCase {
    private function criarRequestComPayload(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($payload));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'placa' => 'ABC1234',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $veiculoCriado = new Veiculo(
            id: 10,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $useCase = $this->createMock(CriarVeiculoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(Veiculo $veiculo) => $veiculo->placa()->getValue() === 'ABC1234'
                    && $veiculo->marca() === 'Toyota'
                    && $veiculo->modelo() === 'Corolla'
            ))
            ->willReturn($veiculoCriado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with(
                $response,
                $this->isInstanceOf(VeiculoResponseDTO::class),
                HttpStatusCodeEnum::Created,
            )
            ->willReturn($response);

        $controller = new CriarVeiculoController(
            $useCase,
            new CriarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoVeiculoJaCadastrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'placa' => 'ABC1234',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $useCase = $this->createMock(CriarVeiculoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(
                VeiculoJaCadastradoException::comPlaca('ABC-1234')
            );

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Veículo com placa ABC-1234 já cadastrado.',
                HttpStatusCodeEnum::Conflict,
            )
            ->willReturn($response);

        $controller = new CriarVeiculoController(
            $useCase,
            new CriarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $useCase = $this->createMock(CriarVeiculoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Placa é obrigatória',
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new CriarVeiculoController(
            $useCase,
            new CriarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPlacaComFormatoInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'placa' => '123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $useCase = $this->createMock(CriarVeiculoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Placa inválida.',
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new CriarVeiculoController(
            $useCase,
            new CriarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }
}
