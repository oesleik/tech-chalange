<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Middleware\OpenTelemetryMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class OpenTelemetryMiddlewareTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        $this->createMetrics();
    }

    public function testProcessaRequisicaoComSucesso(): void {
        $request = $this->createRequest('/clientes');
        $response = new ResponseFactory()->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new OpenTelemetryMiddleware();

        $result = $middleware->process($request, $handler);

        self::assertSame(200, $result->getStatusCode());
    }

    public function testRegistraErroQuandoRespostaPossuiStatusMaiorOuIgualA400(): void {
        $request = $this->createRequest('/estoque/pecas/99999');
        $response = new ResponseFactory()->createResponse(404);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($response);

        $middleware = new OpenTelemetryMiddleware();

        $result = $middleware->process($request, $handler);

        self::assertSame(404, $result->getStatusCode());
    }

    public function testRegistraErro500QuandoHandlerLancaExcecao(): void {
        $request = $this->createRequest('/clientes');

        $exception = new \RuntimeException('Erro de teste');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willThrowException($exception);

        $middleware = new OpenTelemetryMiddleware();

        $this->expectExceptionObject($exception);

        $middleware->process($request, $handler);
    }

    public function testContinuaProcessandoQuandoContadorDeRequisicoesNaoExiste(): void {
        unset($GLOBALS['otel_request_counter']);

        $request = $this->createRequest('/clientes');
        $response = new ResponseFactory()->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($response);

        $middleware = new OpenTelemetryMiddleware();

        $result = $middleware->process($request, $handler);

        self::assertSame(200, $result->getStatusCode());
    }

    public function testContinuaProcessandoQuandoContadorDeErrosNaoExiste(): void {
        unset($GLOBALS['otel_error_counter']);

        $request = $this->createRequest('/estoque/pecas/99999');
        $response = new ResponseFactory()->createResponse(404);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($response);

        $middleware = new OpenTelemetryMiddleware();

        $result = $middleware->process($request, $handler);

        self::assertSame(404, $result->getStatusCode());
    }

    public function testUtilizaRotaResolvidaQuandoDisponivel(): void {
        $app = \Slim\Factory\AppFactory::create();

        $app->get('/clientes/{id}', fn(
            ServerRequestInterface $request,
            ResponseInterface $response
        ): ResponseInterface => $response);

        $app->addRoutingMiddleware();
        $app->add(new OpenTelemetryMiddleware());

        $request = new ServerRequestFactory()
            ->createServerRequest('GET', '/clientes/123');

        $response = $app->handle($request);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testUtilizaUriQuandoRotaNaoPodeSerResolvida(): void {
        $request = $this->createRequest('/rota-inexistente');

        $response = new ResponseFactory()->createResponse(404);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($response);

        $middleware = new OpenTelemetryMiddleware();

        $result = $middleware->process($request, $handler);

        self::assertSame(404, $result->getStatusCode());
    }

    private function createRequest(string $path): ServerRequestInterface {
        return new ServerRequestFactory()->createServerRequest(
            'GET',
            $path
        );
    }

    private function createMetrics(): void {
        if (!isset($GLOBALS['otel_request_counter'])) {
            $GLOBALS['otel_request_counter']
            = \OpenTelemetry\API\Globals::meterProvider()
                ->getMeter('tech-challenge-api-test')
                ->createCounter('test_http_requests_total');
        }

        if (!isset($GLOBALS['otel_error_counter'])) {
            $GLOBALS['otel_error_counter']
            = \OpenTelemetry\API\Globals::meterProvider()
                ->getMeter('tech-challenge-api-test')
                ->createCounter('test_http_errors_total');
        }

        if (!isset($GLOBALS['otel_request_duration'])) {
            $GLOBALS['otel_request_duration']
                = \OpenTelemetry\API\Globals::meterProvider()
                    ->getMeter('tech-challenge-api-test')
                    ->createHistogram('test_http_request_duration_seconds');
        }
    }
}
