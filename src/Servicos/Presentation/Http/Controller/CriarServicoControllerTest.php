<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoInputDTO;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\CriarServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarServicoControllerTest extends TestCase {
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
            'descricao' => 'Revisão',
            'valor_unitario' => 145,
        ]);

        $servicoCriado = Servico::reconstituir(123, 'Revisão', new ValorUnitario(145.0));

        $useCase = $this->createMock(CriarServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(CriarServicoInputDTO $input) => $input->descricao === 'Revisão'
                    && $input->valorUnitario === 145.0
            ))
            ->willReturn($servicoCriado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $controller = new CriarServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute($request, $response));
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'descricao' => '',
            'valor_unitario' => -1,
        ]);

        $useCase = $this->createMock(CriarServicoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with($response, 'Descrição é obrigatória.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new CriarServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute($request, $response));
    }
}
