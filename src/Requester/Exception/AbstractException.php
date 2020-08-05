<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use RuntimeException;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Throwable;
use function Safe\sprintf;

abstract class AbstractException extends RuntimeException
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface $response, ?string $message = null, ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message ?? sprintf('Unsuccessful response received. Status code = %u.', $response->getStatusCode()), $code, $previous);
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
