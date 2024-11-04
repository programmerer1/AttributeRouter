<?php
declare(strict_types=1);

namespace AttributeRouter\Exception;

use RuntimeException;
use Throwable;

class NotFoundHttpException extends RuntimeException
{
    public function __construct(string $message = "", int $statusCode = 404, Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}