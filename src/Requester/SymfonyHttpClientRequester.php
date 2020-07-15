<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyHttpClientRequester implements RequesterInterface
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $headers, $requestData = null): Response
    {
        $response = $this->client->request($method, $uri, [
            'headers' => $headers,
            'body' => $requestData,
        ]);

        return Response::fromResponse($response);
    }
}
