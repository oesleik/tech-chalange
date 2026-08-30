<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoInputDTO;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\EditarServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarServicoControllerTest extends TestCase {
    /** @param array<string, mixed> $payload */
    private function criarRequestComPayload(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn((string) json_encode($payload));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'descricao' => 'Diagnóstico',
            'valor_unitario' => 80,
        ]);

        $servicoAtualizado = Servico::reconstituir(123, 'Diagnóstico', new ValorUnitario(80.0));

        $useCase = $this->createMock(EditarServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(123, $this->callback(
                fn(EditarServicoInputDTO $input) => $input->descricao === 'Diagnóstico'
                    && $input->valorUnitario === 80.0
            ))
            ->willReturn($servicoAtualizado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $controller = new EditarServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $request, $response));
    }

    public function testExecuteQuandoServicoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['descricao' => 'Diagnóstico']);

        $useCase = $this->createMock(EditarServicoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(ServicoNaoEncontradoException::comId(123));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with($response, 'Serviço com id 123 não encontrado.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new EditarServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $request, $response));
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['valor_unitario' => -1]);

        $useCase = $this->createMock(EditarServicoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with($response, 'Valor unitário não pode ser negativo.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new EditarServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $request, $response));
    }
}
