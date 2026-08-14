# Exemplos por peça

- [README.md](./README.md)
- [ESTRUTURA.md](./ESTRUTURA.md)

Domínio ilustrativo: veículo. Troque nomes pelo seu módulo.

---

## Domain - Value Object

```php
final class Placa {
    public function __construct(private string $placa) {
        $this->placa = strtoupper(str_replace(['-', ' '], '', trim($placa)));

        if (strlen($this->placa) !== 7) {
            throw new InvalidArgumentException('Placa inválida.');
        }
    }

    public function getValue(): string {
        return $this->placa;
    }

    public function getFormattedValue(): string {
        return substr($this->placa, 0, 3) . '-' . substr($this->placa, 3);
    }
}
```

## Domain - Entity

```php
final class Veiculo {
    private function __construct(
        private ?int $id,
        private Placa $placa,
        private string $marca,
        private string $modelo,
    ) {
        if (trim($marca) === '' || trim($modelo) === '') {
            throw new InvalidArgumentException('Marca e modelo são obrigatórios.');
        }
        $this->marca = trim($marca);
        $this->modelo = trim($modelo);
    }

    public static function criar(Placa $placa, string $marca, string $modelo): self {
        return new self(null, $placa, $marca, $modelo);
    }

    public static function reconstituir(int $id, Placa $placa, string $marca, string $modelo): self {
        return new self($id, $placa, $marca, $modelo);
    }

    public function id(): ?int { return $this->id; }
    public function placa(): Placa { return $this->placa; }
    public function marca(): string { return $this->marca; }
    public function modelo(): string { return $this->modelo; }

    public function comId(int $id): self {
        return new self($id, $this->placa, $this->marca, $this->modelo);
    }

    public function comPlaca(Placa $placa): self {
        return new self($this->id, $placa, $this->marca, $this->modelo);
    }

    public function comMarca(string $marca): self {
        return new self($this->id, $this->placa, $marca, $this->modelo);
    }

    public function comModelo(string $modelo): self {
        return new self($this->id, $this->placa, $this->marca, $modelo);
    }
}
```

## Domain - Exception

```php
final class VeiculoNaoEncontradoException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Veículo com id {$id} não encontrado.");
    }
}

final class VeiculoJaCadastradoException extends RuntimeException {
    public static function comPlaca(string $placa): self {
        return new self("Veículo com placa {$placa} já cadastrado.");
    }
}
```

---

## Application - Gateway (port)

```php
interface VeiculoGatewayInterface {
    public function buscarPorId(int $id): ?Veiculo;
    public function buscarPorPlaca(Placa $placa): ?Veiculo;
    public function inserir(Veiculo $veiculo): Veiculo;
    public function atualizar(Veiculo $veiculo): Veiculo;
    /** @return Veiculo[] */
    public function listar(FiltroListagemVeiculo $filtro): array;
    public function contar(FiltroListagemVeiculo $filtro): int;
}

final class FiltroListagemVeiculo {
    public function __construct(
        public readonly ?Placa $placa,
        public readonly ?string $marca,
        public readonly ?string $modelo,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {}
}
```

## Application - DTOs

```php
final class CriarVeiculoInputDTO {
    public function __construct(
        public readonly string $placa,
        public readonly string $marca,
        public readonly string $modelo,
    ) {}
}

final class EditarVeiculoInputDTO {
    public function __construct(
        public readonly ?string $placa = null,
        public readonly ?string $marca = null,
        public readonly ?string $modelo = null,
    ) {}
}

final class ListarVeiculoInputDTO {
    public function __construct(
        public readonly ?Placa $placa = null,
        public readonly ?string $marca = null,
        public readonly ?string $modelo = null,
        public readonly int $pagina = 1,
        public readonly int $porPagina = 20,
    ) {}
}

final class ListarVeiculoOutputDTO {
    /** @param Veiculo[] $veiculos */
    public function __construct(
        public readonly array $veiculos,
        public readonly int $total,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {}
}
```

## Application - Use Cases

```php
final class CriarVeiculoUseCase {
    public function __construct(private readonly VeiculoGatewayInterface $gateway) {}

    public function executar(CriarVeiculoInputDTO $input): Veiculo {
        $placa = new Placa($input->placa);
        $veiculo = Veiculo::criar($placa, $input->marca, $input->modelo);

        if ($this->gateway->buscarPorPlaca($placa) !== null) {
            throw VeiculoJaCadastradoException::comPlaca($placa->getFormattedValue());
        }

        return $this->gateway->inserir($veiculo);
    }
}

final class ObterVeiculoUseCase {
    public function __construct(private readonly VeiculoGatewayInterface $gateway) {}

    public function executar(int $id): Veiculo {
        $veiculo = $this->gateway->buscarPorId($id);
        if ($veiculo === null) {
            throw VeiculoNaoEncontradoException::comId($id);
        }
        return $veiculo;
    }
}

final class EditarVeiculoUseCase {
    public function __construct(private readonly VeiculoGatewayInterface $gateway) {}

    public function executar(int $id, EditarVeiculoInputDTO $input): Veiculo {
        $veiculo = $this->gateway->buscarPorId($id)
            ?? throw VeiculoNaoEncontradoException::comId($id);

        if ($input->placa !== null) {
            $nova = new Placa($input->placa);
            if ($nova->getValue() !== $veiculo->placa()->getValue()) {
                if ($this->gateway->buscarPorPlaca($nova) !== null) {
                    throw VeiculoJaCadastradoException::comPlaca($nova->getFormattedValue());
                }
                $veiculo = $veiculo->comPlaca($nova);
            }
        }
        if ($input->marca !== null) {
            $veiculo = $veiculo->comMarca($input->marca);
        }
        if ($input->modelo !== null) {
            $veiculo = $veiculo->comModelo($input->modelo);
        }

        return $this->gateway->atualizar($veiculo);
    }
}

final class ListarVeiculoUseCase {
    public function __construct(private readonly VeiculoGatewayInterface $gateway) {}

    public function executar(ListarVeiculoInputDTO $input): ListarVeiculoOutputDTO {
        $filtro = new FiltroListagemVeiculo(
            $input->placa, $input->marca, $input->modelo, $input->pagina, $input->porPagina,
        );

        return new ListarVeiculoOutputDTO(
            veiculos: $this->gateway->listar($filtro),
            total: $this->gateway->contar($filtro),
            pagina: $input->pagina,
            porPagina: $input->porPagina,
        );
    }
}
```

---

## Infrastructure - Mapper + Gateway

```php
final class VeiculoMapper {
    /** @param array<string, mixed> $linha */
    public static function paraEntidade(array $linha): Veiculo {
        return Veiculo::reconstituir(
            (int) $linha['id'],
            new Placa($linha['placa']),
            $linha['marca'],
            $linha['modelo'],
        );
    }
}

final class VeiculoGateway implements VeiculoGatewayInterface {
    private const TABELA = 'veiculos';

    public function __construct(private readonly DbConnectionInterface $connection) {}

    public function buscarPorId(int $id): ?Veiculo {
        $linhas = $this->connection->buscarPorParametros(self::TABELA, null, ['id' => $id]);
        return $linhas === [] ? null : VeiculoMapper::paraEntidade($linhas[0]);
    }

    public function buscarPorPlaca(Placa $placa): ?Veiculo {
        $linhas = $this->connection->buscarPorParametros(self::TABELA, null, ['placa' => $placa->getValue()]);
        return $linhas === [] ? null : VeiculoMapper::paraEntidade($linhas[0]);
    }

    public function inserir(Veiculo $veiculo): Veiculo {
        $id = $this->connection->inserir(self::TABELA, [
            'placa' => $veiculo->placa()->getValue(),
            'marca' => $veiculo->marca(),
            'modelo' => $veiculo->modelo(),
        ]);
        return $veiculo->comId($id);
    }

    public function atualizar(Veiculo $veiculo): Veiculo {
        $this->connection->atualizar(
            self::TABELA,
            [
                'placa' => $veiculo->placa()->getValue(),
                'marca' => $veiculo->marca(),
                'modelo' => $veiculo->modelo(),
            ],
            ['id' => $veiculo->id()],
        );
        return $veiculo;
    }

    public function listar(FiltroListagemVeiculo $filtro): array {
        $offset = ($filtro->pagina - 1) * $filtro->porPagina;
        $registros = $this->connection->buscarComFiltro(
            tabela: self::TABELA,
            condicoesExatas: $filtro->placa ? ['placa' => $filtro->placa->getValue()] : [],
            condicoesParciais: array_filter([
                'marca' => $filtro->marca,
                'modelo' => $filtro->modelo,
            ]),
            limite: $filtro->porPagina,
            offset: $offset,
        );
        return array_map(VeiculoMapper::paraEntidade(...), $registros);
    }

    public function contar(FiltroListagemVeiculo $filtro): int {
        return $this->connection->contarComFiltro(
            tabela: self::TABELA,
            condicoesExatas: $filtro->placa ? ['placa' => $filtro->placa->getValue()] : [],
            condicoesParciais: array_filter([
                'marca' => $filtro->marca,
                'modelo' => $filtro->modelo,
            ]),
        );
    }
}
```

---

## Presentation - Mapper + Response DTO

```php
final class CriarVeiculoMapper {
    public static function parse(array $data): CriarVeiculoInputDTO {
        foreach (['placa', 'marca', 'modelo'] as $campo) {
            if (empty($data[$campo]) || !is_string($data[$campo])) {
                throw new InvalidArgumentException(ucfirst($campo) . ' é obrigatória');
            }
        }
        return new CriarVeiculoInputDTO(trim($data['placa']), trim($data['marca']), trim($data['modelo']));
    }
}

final class EditarVeiculoMapper {
    public static function parse(array $data): EditarVeiculoInputDTO {
        $campo = static function (array $data, string $nome): ?string {
            if (!array_key_exists($nome, $data) || $data[$nome] === null) {
                return null;
            }
            if (!is_string($data[$nome])) {
                throw new InvalidArgumentException("Campo '{$nome}' deve ser string.");
            }
            $v = trim($data[$nome]);
            return $v === '' ? null : $v;
        };

        return new EditarVeiculoInputDTO(
            $campo($data, 'placa'),
            $campo($data, 'marca'),
            $campo($data, 'modelo'),
        );
    }
}

final class ListarVeiculoMapper {
    public static function fromQueryParams(array $params): ListarVeiculoInputDTO {
        $pagina = max(1, (int) ($params['pagina'] ?? 1));
        $porPagina = min(100, max(1, (int) ($params['porPagina'] ?? 20)));

        return new ListarVeiculoInputDTO(
            placa: isset($params['placa']) ? new Placa($params['placa']) : null,
            marca: isset($params['marca']) && trim($params['marca']) !== '' ? trim($params['marca']) : null,
            modelo: isset($params['modelo']) && trim($params['modelo']) !== '' ? trim($params['modelo']) : null,
            pagina: $pagina,
            porPagina: $porPagina,
        );
    }
}

final class VeiculoResponseDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $placa,
        public readonly string $marca,
        public readonly string $modelo,
    ) {}

    public static function fromEntity(Veiculo $veiculo): self {
        return new self(
            (int) $veiculo->id(),
            $veiculo->placa()->getFormattedValue(),
            $veiculo->marca(),
            $veiculo->modelo(),
        );
    }
}
```

## Presentation - Controller + Router

```php
final class CriarVeiculoController {
    public function __construct(
        private CriarVeiculoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $veiculo = $this->useCase->executar(CriarVeiculoMapper::parse($payload));

            return $this->presenter->success(
                $response,
                VeiculoResponseDTO::fromEntity($veiculo),
                HttpStatusCodeEnum::Created,
            );
        } catch (VeiculoJaCadastradoException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

final class CriarVeiculoRouter {
    public function __construct(private CriarVeiculoController $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($request, $response);
    }
}
```

Mesmo padrão para Obter/Editar/Listar: Router `__invoke` → Controller → (Mapper) → UseCase → ResponseDTO / Presenter.  
Exceptions: não encontrado → 404, conflito → 409, `InvalidArgumentException` → 400.

---

## Service Container

```php
// core.definitions.php
VeiculoGatewayInterface::class => fn(\DI\Container $c) => new VeiculoGateway(
    $c->get(DbConnectionInterface::class),
),

// router.php
$g->post('/', CriarVeiculoRouter::class);
$g->get('/', ListarVeiculoRouter::class);
$g->get('/{id:[0-9]+}', ObterVeiculoRouter::class);
$g->patch('/{id:[0-9]+}', EditarVeiculoRouter::class);
```

UseCase / Controller / Router: autowire.

---

## Teste de Use Case

```php
final class CriarVeiculoUseCaseTest extends TestCase {
    public function testInsereQuandoPlacaDisponivel(): void {
        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway->method('buscarPorPlaca')->willReturn(null);
        $gateway->method('inserir')->willReturnCallback(
            fn(Veiculo $v) => $v->comId(10),
        );

        $resultado = (new CriarVeiculoUseCase($gateway))->executar(
            new CriarVeiculoInputDTO('ABC1D23', 'Toyota', 'Corolla'),
        );

        $this->assertSame(10, $resultado->id());
    }

    public function testLancaQuandoPlacaJaExiste(): void {
        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway->method('buscarPorPlaca')->willReturn(
            Veiculo::reconstituir(1, new Placa('ABC1D23'), 'X', 'Y'),
        );
        $gateway->expects($this->never())->method('inserir');

        $this->expectException(VeiculoJaCadastradoException::class);
        (new CriarVeiculoUseCase($gateway))->executar(
            new CriarVeiculoInputDTO('ABC1D23', 'Toyota', 'Corolla'),
        );
    }
}
```
