<?php

declare(strict_types=1);

namespace App\Core\Presentation\Http;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class JsonPresenter implements PresenterInterface {
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    public function success(ResponseInterface $response, object $data, HttpStatusCodeEnum $status = HttpStatusCodeEnum::Ok): ResponseInterface {
        $response->getBody()->write($this->serializer->serialize($data, "json"));

        return $response
            ->withStatus($status->value)
            ->withHeader('Content-Type', 'application/json');
    }

    public function error(ResponseInterface $response, string $message, HttpStatusCodeEnum $status = HttpStatusCodeEnum::BadRequest): ResponseInterface {
        $response->getBody()->write(
            $this->serializer->serialize((object) ['erro' => $message], "json")
        );

        return $response
            ->withStatus($status->value)
            ->withHeader('Content-Type', 'application/json');
    }
}
