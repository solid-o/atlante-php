<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseFactory;
use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\Response\ResponseInterface;

use function array_filter;
use function get_debug_type;
use function is_callable;
use function is_resource;
use function is_string;
use function sprintf;

class PsrClientRequester implements RequesterInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(private ClientInterface $client, private RequestFactoryInterface $requestFactory, private StreamFactoryInterface $streamFactory, ResponseFactoryInterface|null $responseFactory = null)
    {
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $headers, mixed $requestData = null, callable|null $filter = null): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        foreach ($headers as $key => $value) {
            if ($value === null) {
                continue;
            }

            $request = $request->withHeader($key, array_filter((array) $value, static fn ($value) => $value !== null));
        }

        if ($requestData !== null) {
            if (is_callable($requestData)) {
                $requestData = $requestData();
            }

            if (! is_string($requestData) && ! is_resource($requestData)) {
                throw new InvalidArgumentException(sprintf('Request body should be a string or a stream resource, "%s" passed', get_debug_type($requestData)));
            }

            $stream = is_resource($requestData) ?
                $this->streamFactory->createStreamFromResource($requestData) :
                $this->streamFactory->createStream($requestData);
            $request = $request->withBody($stream);
        }

        $response = $this->client->sendRequest($request);

        return $this->responseFactory->fromResponse($response, $filter);
    }
}
