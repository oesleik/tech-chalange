<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Infrastructure\NewRelic\NewRelicAgentInterface;
use App\Middleware\NewRelicErrorHandler;
use App\Middleware\NewRelicMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class NewRelicMiddlewareTest extends TestCase {
    public function testNaoInstrumentaQuandoAExtensaoNaoEstaCarregada(): void {
        $agent = new RecordingNewRelicAgent(loaded: false);
        $request = $this->request('GET', '/clientes/15');
        $response = (new ResponseFactory())->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $result = (new NewRelicMiddleware($agent))->process($request, $handler);

        self::assertSame(200, $result->getStatusCode());
        self::assertSame([], $agent->transactions);
        self::assertSame([], $agent->parameters);
    }

    public function testNomeiaTransacaoPeloPadraoDaRota(): void {
        $agent = new RecordingNewRelicAgent();
        $app = \Slim\Factory\AppFactory::create();
        $app->get('/clientes/{id:[0-9]+}', fn(
            ServerRequestInterface $request,
            ResponseInterface $response,
        ): ResponseInterface => $response);
        $app->add(new NewRelicMiddleware($agent));
        $app->addRoutingMiddleware();

        $response = $app->handle($this->request('GET', '/clientes/15'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['GET /clientes/{id:[0-9]+}'], $agent->transactions);
        self::assertSame('GET', $agent->parameters['http.method']);
        self::assertSame('/clientes/{id:[0-9]+}', $agent->parameters['http.route']);
        self::assertSame(200, $agent->parameters['http.status_code']);
        self::assertArrayNotHasKey('request.id', $agent->parameters);
        self::assertSame([], $agent->errors);
    }

    public function testAdicionaRequestIdQuandoOHeaderExiste(): void {
        $agent = new RecordingNewRelicAgent();
        $request = $this->request('POST', '/clientes')->withHeader('X-Request-Id', 'req-1');
        $handler = $this->handler(201);

        (new NewRelicMiddleware($agent))->process($request, $handler);

        self::assertSame('req-1', $agent->parameters['request.id']);
    }

    public function testIgnoraProbeDeSaude(): void {
        $agent = new RecordingNewRelicAgent();
        $app = \Slim\Factory\AppFactory::create();
        $app->get('/health', fn(
            ServerRequestInterface $request,
            ResponseInterface $response,
        ): ResponseInterface => $response);
        $app->add(new NewRelicMiddleware($agent));
        $app->addRoutingMiddleware();

        $response = $app->handle($this->request('GET', '/health'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $agent->transactions);
        self::assertSame(1, $agent->ignored);
    }

    public function testIgnoraHealthzPeloCaminho(): void {
        $agent = new RecordingNewRelicAgent();
        $handler = $this->handler(200);

        (new NewRelicMiddleware($agent))->process($this->request('GET', '/healthz'), $handler);

        self::assertSame([], $agent->transactions);
        self::assertSame(1, $agent->ignored);
    }

    public function testNotificaResposta5xx(): void {
        $agent = new RecordingNewRelicAgent();
        $handler = $this->handler(503);

        (new NewRelicMiddleware($agent))->process($this->request('GET', '/clientes'), $handler);

        self::assertCount(1, $agent->errors);
        self::assertSame(503, $agent->parameters['http.status_code']);
    }

    public function testHandlerDeErroNotificaExcecao5xxEPreservaAResposta(): void {
        $agent = new RecordingNewRelicAgent();
        $request = $this->request('GET', '/clientes');
        $exception = new \RuntimeException('falha interna');
        $next = new FixedErrorHandler((new ResponseFactory())->createResponse(500));

        $response = (new NewRelicErrorHandler($agent, $next))(
            $request,
            $exception,
            false,
            true,
            true,
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame([$exception], $agent->errors);
        self::assertSame(500, $agent->parameters['http.status_code']);
        self::assertSame([$request, $exception, false, true, true], $next->calls[0]);
    }

    public function testHandlerDeErroIgnoraFalhaDeProbe(): void {
        $agent = new RecordingNewRelicAgent();
        $request = $this->request('GET', '/health');
        $next = new FixedErrorHandler((new ResponseFactory())->createResponse(500));

        (new NewRelicErrorHandler($agent, $next))(
            $request,
            new \RuntimeException('banco fora'),
            false,
            false,
            false,
        );

        self::assertSame([], $agent->errors);
    }

    public function testHandlerDeErroNaoNotifica404(): void {
        $agent = new RecordingNewRelicAgent();
        $request = $this->request('GET', '/ausente');
        $next = new FixedErrorHandler((new ResponseFactory())->createResponse(404));

        $response = (new NewRelicErrorHandler($agent, $next))(
            $request,
            new HttpNotFoundException($request),
            false,
            false,
            false,
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame([], $agent->errors);
    }

    public function testAgenteRealNaoFazNadaSemAExtensao(): void {
        if (extension_loaded('newrelic')) {
            self::markTestSkipped('A extensão newrelic está carregada neste ambiente.');
        }

        $agent = new \App\Infrastructure\NewRelic\NewRelicAgent();

        self::assertFalse($agent->isLoaded());
        $agent->nameTransaction('GET /clientes/{id}');
        $agent->addCustomParameter('http.method', 'GET');
        $agent->ignoreTransaction();
        $agent->noticeError(new \RuntimeException('x'));
    }

    private function request(string $method, string $path): ServerRequestInterface {
        return (new ServerRequestFactory())->createServerRequest($method, $path);
    }

    private function handler(int $status): RequestHandlerInterface {
        $response = (new ResponseFactory())->createResponse($status);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }
}

final class RecordingNewRelicAgent implements NewRelicAgentInterface {
    /** @var list<string> */
    public array $transactions = [];

    /** @var array<string, string|int|float|bool> */
    public array $parameters = [];

    public int $ignored = 0;

    /** @var list<\Throwable> */
    public array $errors = [];

    public function __construct(
        private readonly bool $loaded = true,
    ) {}

    public function isLoaded(): bool {
        return $this->loaded;
    }

    public function nameTransaction(string $name): void {
        $this->transactions[] = $name;
    }

    public function addCustomParameter(string $key, string|int|float|bool $value): void {
        $this->parameters[$key] = $value;
    }

    public function ignoreTransaction(): void {
        $this->ignored++;
    }

    public function noticeError(\Throwable $error): void {
        $this->errors[] = $error;
    }
}

final class FixedErrorHandler implements ErrorHandlerInterface {
    /** @var list<array{0: ServerRequestInterface, 1: \Throwable, 2: bool, 3: bool, 4: bool}> */
    public array $calls = [];

    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        $this->calls[] = [$request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails];

        return $this->response;
    }
}
