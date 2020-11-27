<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Solido\Atlante\Requester\Response\SymfonyHttpClientResponseFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyHttpClientRequester implements RequesterInterface
{
    private HttpClientInterface $client;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(HttpClientInterface $client, ?ResponseFactoryInterface $responseFactory = null)
    {
        $this->client = $client;
        $this->responseFactory = $responseFactory ?? new SymfonyHttpClientResponseFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $uri, array $headers, $requestData = null): ResponseInterface
    {
        $response = $this->client->request($method, $uri, [
            'headers' => $headers,
            'body' => $requestData,
        ]);

        return $this->responseFactory->fromResponse($response);
    }
}
