```php
// router
$app->post("/exemplo/{id:[0-9]+}", [\App\Modulo\Controller::class, "exemplo"]);
```

```php
// Controller
use OpenApi\Attributes as OA;

#[OA\Post(
	path: '/exemplo/{id}',
	operationId: 'exemplo',
	summary: 'Exemplo de controller',
	tags: ['Exemplo']
)]
#[OA\Parameter(
	name: 'id',
	in: 'path',
	required: true,
	schema: new OA\Schema(type: 'integer')
)]
#[OA\RequestBody(
	required: true,
	content: new OA\JsonContent(ref: '#/components/schemas/ExemploRequest')
)]
#[OA\Response(
	response: 200,
	description: 'Peça atualizada',
	content: new OA\JsonContent(ref: '#/components/schemas/ExemploResponse')
)]
public function exemplo(
	int $id,
	ServerRequestInterface $request,
	ResponseInterface $response,
	ContractResolver $contractResolver,
): ResponseInterface {
	$payload = $request->getParsedBody();
	$input = $contractResolver->fromArray($payload, ExemploRequest::class);

	$output = ...; // ExemploResponse

	$response->getBody()->write($contractResolver->toJson($output));
	return $response->withHeader('Content-Type', 'application/json');
}
```

```php
// ExemploRequest
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ExemploRequest extends AbstractContract {

	public function __construct(
		#[OA\Property(example: "Nome do exemplo")]
		public string $nome,
		#[OA\Property(example: 21)]
		public int $idade,
	) {
	}

	public static function getConstraints(): Assert\Collection {
		return new Assert\Collection([
			'nome' => [
				new Assert\NotBlank(),
				new Assert\Type('string'),
			],
			'idade' => [
				new Assert\NotNull(),
				new Assert\Type('integer'),
				new Assert\Positive(),
			],
		]);
	}

}
```
