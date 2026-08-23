<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoInputDTO;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\Controller\CriarOrdemServicoController;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarOrdemServicoControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request(['id_cliente' => 10, 'id_veiculo' => 20]);

        $useCase = $this->createMock(CriarOrdemServicoUseCaseInterface::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with($this->isInstanceOf(CriarOrdemServicoInputDTO::class))
            ->willReturn(new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime()));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(OrdemServicoResumidaResponseDTO::class), HttpStatusCodeEnum::Created)
            ->willReturn($response);

        $resultado = new CriarOrdemServicoController($useCase, $presenter)->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request([]);

        $useCase = $this->createMock(CriarOrdemServicoUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, $this->isType('string'), HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $resultado = new CriarOrdemServicoController($useCase, $presenter)->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    private function request(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($payload));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }
}
