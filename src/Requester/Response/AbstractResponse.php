<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

abstract class AbstractResponse implements ResponseInterface
{
    /** @var object|string */
    protected $data;
    protected int $statusCode;

    /**
     * @param object|string $data
     */
    public function __construct(int $statusCode, $data)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    /**
     * @return object|string
     */
    public function getData()
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
