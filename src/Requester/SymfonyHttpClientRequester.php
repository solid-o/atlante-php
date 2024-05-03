<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Solido\Atlante\Requester\Response\SymfonyHttpClientResponseFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_filter;
use function is_string;

class SymfonyHttpClientRequester implements RequesterInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(private HttpClientInterface $client, ResponseFactoryInterface|null $responseFactory = null)
    {
        $this->responseFactory = $responseFactory ?? new SymfonyHttpClientResponseFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $headers, mixed $requestData = null, callable|null $filter = null): ResponseInterface
    {
        $hdrs = [];
        foreach ($headers as $key => $value) {
            if ($value === null) {
                continue;
            }

            $hdrs[$key] = is_string($value) ? $value : array_filter($value, static fn ($value) => $value !== null);
        }

        $response = $this->client->request($method, $uri, [
            'headers' => $hdrs,
            'body' => $requestData,
            'max_redirects' => 0,
        ]);

        return $this->responseFactory->fromResponse($response, $filter);
    }
}
