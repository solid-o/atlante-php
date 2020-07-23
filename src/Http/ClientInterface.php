<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Closure;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Traversable;

interface ClientInterface
{
    /**
     * Performs a request to the API service using the given method.
     *
     * @param array|string|resource|Closure<string>|Traversable<string>|null $requestData
     * @param string[]|string[][]|null                                       $headers
     *
     * @phpstan-param array|string|resource|Closure(): string|Traversable<string>|null $requestData
     */
    public function request(string $method, string $path, $requestData = null, ?array $headers = null): ResponseInterface;

    /**
     * Performs a request to the API service using a DELETE method.
     *
     * @param string[]|string[][]|null $headers
     */
    public function delete(string $path, ?array $headers = null): ResponseInterface;

    /**
     * Performs a request to the API service using a GET method.
     *
     * @param string[]|string[][]|null $headers
     */
    public function get(string $path, ?array $headers = null): ResponseInterface;

    /**
     * Performs a request to the API service using a POST method.
     *
     * @param array|string|resource|Closure<string>|Traversable<string>|null $requestData
     * @param string[]|string[][]|null                                       $headers
     *
     * @phpstan-param array|string|resource|Closure(): string|Traversable<string>|null $requestData
     */
    public function post(string $path, $requestData = null, ?array $headers = null): ResponseInterface;

    /**
     * Performs a request to the API service using a PUT method.
     *
     * @param array|string|resource|Closure<string>|Traversable<string>|null $requestData
     * @param string[]|string[][]|null                                       $headers
     *
     * @phpstan-param array|string|resource|Closure(): string|Traversable<string>|null $requestData
     */
    public function put(string $path, $requestData = null, ?array $headers = null): ResponseInterface;

    /**
     * Performs a request to the API service using a PATCH method.
     *
     * @param array|string|resource|Closure<string>|Traversable<string>|null $requestData
     * @param string[]|string[][]|null                                       $headers
     *
     * @phpstan-param array|string|resource|Closure(): string|Traversable<string>|null $requestData
     */
    public function patch(string $path, $requestData = null, ?array $headers = null): ResponseInterface;
}
