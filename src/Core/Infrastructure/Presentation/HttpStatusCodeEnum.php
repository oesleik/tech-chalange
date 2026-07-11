<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Presentation;

enum HttpStatusCodeEnum: int {
    case Ok = 200;
    case Created = 201;
    case NoContent = 204;
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case Conflict = 409;
    case UnprocessableEntity = 422;
    case InternalServerError = 500;
}
