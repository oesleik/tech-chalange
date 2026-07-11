<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Presentation;

use App\Core\Contract\ContractResolver;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use Psr\Http\Message\ResponseInterface;

final class JsonPresenter implements PresenterInterface {
    public function __construct(
        private readonly ContractResolver $contractResolver,
    ) {}

    public function success(ResponseInterface $response, object $data, HttpStatusCodeEnum $status = HttpStatusCodeEnum::Ok): ResponseInterface {
        $response->getBody()->write($this->contractResolver->toJson($data));

        return $response
            ->withStatus($status->value)
            ->withHeader('Content-Type', 'application/json');
    }

    public function error(ResponseInterface $response, string $message, HttpStatusCodeEnum $status = HttpStatusCodeEnum::BadRequest): ResponseInterface {
        $response->getBody()->write(
            $this->contractResolver->toJson((object) ['erro' => $message])
        );

        return $response
            ->withStatus($status->value)
            ->withHeader('Content-Type', 'application/json');
    }
}
