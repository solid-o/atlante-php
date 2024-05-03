<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

abstract class AbstractResponse implements ResponseInterface
{
    public function __construct(protected int $statusCode, protected HeaderBag $headers, protected mixed $data)
    {
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getHeaders(): HeaderBag
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
