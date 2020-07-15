<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function is_resource;

class PsrClientRequester implements RequesterInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $headers, $requestData = null): Response
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($requestData !== null) {
            $stream = is_resource($requestData) ? $this->streamFactory->createStreamFromResource($requestData) :
                $this->streamFactory->createStream($requestData);
            $request = $request->withBody($stream);
        }

        $response = $this->client->sendRequest($request);

        return Response::fromResponse($response);
    }
}
