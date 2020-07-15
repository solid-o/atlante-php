<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

interface RequesterInterface
{
    /**
     * Performs a request.
     * Returns a response with parsed data, if no error is present.
     *
     * @param array<string, string|string[]> $headers
     * @param string|resource|null $requestData
     */
    public function request(string $method, string $uri, array $headers, $requestData = null): Response;
}
