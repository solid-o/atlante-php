<?php

declare(strict_types=1);

namespace Solido\Atlante\Exception;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    private int $statusCode;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, ?string $message = null, ?Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message ?? '', $code ?? 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     *
     * @param array<string, string> $headers Response headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
