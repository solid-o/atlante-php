<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

class LazyResponse implements ResponseInterface
{
    /** @var callable(): ResponseInterface */
    private $responseBuilder;
    private ResponseInterface $response;

    /**
     * @param callable(): ResponseInterface $responseBuilder
     */
    public function __construct(callable $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->getResponse()->getData();
    }

    public function getHeaders(): HeaderBag
    {
        return $this->getResponse()->getHeaders();
    }

    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    private function getResponse(): ResponseInterface
    {
        if (! isset($this->response)) {
            $this->response = ($this->responseBuilder)();
        }

        return $this->response;
    }
}
