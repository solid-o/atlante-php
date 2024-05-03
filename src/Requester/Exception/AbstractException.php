<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use RuntimeException;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Throwable;

use function sprintf;

abstract class AbstractException extends RuntimeException
{
    public function __construct(protected ResponseInterface $response, string|null $message = null, Throwable|null $previous = null)
    {
        parent::__construct($message ?? sprintf('Unsuccessful response received. Status code = %u.', $response->getStatusCode()), 0, $previous);
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
