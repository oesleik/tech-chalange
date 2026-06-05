```php
// router
$app->get("/exemplo", [\App\Modulo\Controller::class, "exemplo"]);
```

```php
// Controller
public function exemplo(
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

readonly class ContratoRequest extends AbstractContract {

	public function __construct(
		public string $nome,
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
