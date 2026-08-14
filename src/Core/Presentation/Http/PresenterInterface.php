<?php

declare(strict_types=1);

namespace App\Core\Presentation\Http;

use Psr\Http\Message\ResponseInterface;

interface PresenterInterface {
    /**
     * Monta uma resposta de sucesso, serializando $data como JSON.
     */
    public function success(ResponseInterface $response, object $data, HttpStatusCodeEnum $status = HttpStatusCodeEnum::Ok): ResponseInterface;

    /**
     * Monta uma resposta de erro no formato padrão { "erro": "..." }.
     */
    public function error(ResponseInterface $response, string $message, HttpStatusCodeEnum $status = HttpStatusCodeEnum::BadRequest): ResponseInterface;
}
