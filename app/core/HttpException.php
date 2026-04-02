<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

class HttpException extends Exception
{
    public function __construct(
        string $message = 'Terjadi kesalahan pada aplikasi.',
        protected int $statusCode = 500
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
