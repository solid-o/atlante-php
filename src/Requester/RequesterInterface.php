<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Solido\Atlante\Requester\Response\ResponseInterface;

interface RequesterInterface
{
    /**
     * Performs a request.
     * Returns a response with parsed data, if no error is present.
     *
     * @param array<string, string|string[]> $headers
     * @param callable|string|resource|null  $requestData
     */
    public function request(string $method, string $uri, array $headers, $requestData = null, bool $lazy = false, ?callable $filter = null): ResponseInterface;
}
