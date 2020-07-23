<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyHttpClientResponse;

interface ResponseFactoryInterface
{
    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     */
    public function fromResponse($response): ResponseInterface;
}
