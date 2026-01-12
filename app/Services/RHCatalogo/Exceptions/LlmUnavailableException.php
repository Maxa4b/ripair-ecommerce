<?php

namespace App\Services\RHCatalogo\Exceptions;

use RuntimeException;
use Throwable;

class LlmUnavailableException extends RuntimeException
{
    public ?string $endpoint;
    public ?int $httpStatus;

    public function __construct(string $message = 'LLM unavailable', ?string $endpoint = null, ?int $httpStatus = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->endpoint = $endpoint;
        $this->httpStatus = $httpStatus;
    }
}

