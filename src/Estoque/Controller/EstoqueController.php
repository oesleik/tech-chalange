<?php

declare(strict_types=1);

namespace App\Estoque\Controller;

use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Estoque\Contract\EntradaEstoqueContract;
use App\Estoque\Repository\EstoqueRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EstoqueController {
    public function __construct(
        private readonly EstoqueRepository $repository,
        private readonly ContractResolver  $contractResolver,
    ) {}

    public function registrarEntrada(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $body     = (array) $request->getParsedBody();
            $contract = $this->contractResolver->fromArray($body, EntradaEstoqueContract::class);
            $entrada  = $this->repository->registrarEntrada($contract->id_peca, $contract->quantidade);

            $response->getBody()->write(json_encode($entrada));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (InvalidContractException $e) {
            $errors = [];
            foreach ($e->getViolations() as $violation) {
                $field          = trim($violation->getPropertyPath(), '[]');
                $errors[$field] = $violation->getMessage();
            }

            $response->getBody()->write(json_encode(['errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);

        } catch (\RuntimeException $e) {
            $status = $e->getCode() === 404 ? 404 : 400;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }
    }
}
