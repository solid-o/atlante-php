<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseFactory;
use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseInterface;

use function is_callable;
use function is_resource;

class PsrClientRequester implements RequesterInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, ?ResponseFactoryInterface $responseFactory = null)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $uri, array $headers, $requestData = null): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($requestData !== null) {
            if (is_callable($requestData)) {
                $requestData = $requestData();
            }

            if (! is_string($requestData) && ! is_resource($requestData)) {
                throw new \InvalidArgumentException(\Safe\sprintf('Request body should be a string or a stream resource, "%s" passed', get_debug_type($requestData)));
            }

            $stream = is_resource($requestData) ?
                $this->streamFactory->createStreamFromResource($requestData) :
                $this->streamFactory->createStream($requestData);
            $request = $request->withBody($stream);
        }

        $response = $this->client->sendRequest($request);

        return $this->responseFactory->fromResponse($response);
    }
}
