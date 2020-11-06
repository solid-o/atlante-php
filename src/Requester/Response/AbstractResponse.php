<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

abstract class AbstractResponse implements ResponseInterface
{
    /** @var mixed[]|object|string */
    protected $data;
    protected HeaderBag $headers;
    protected int $statusCode;

    /**
     * @param array<string, string|string[]> $headers
     * @param mixed[]|object|string $data
     */
    public function __construct(int $statusCode, array $headers, $data)
    {
        $this->statusCode = $statusCode;
        $this->headers = new HeaderBag($headers);
        $this->data = $data;
    }

    /**
     * @return mixed[]|object|string
     */
    public function getData()
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
