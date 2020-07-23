<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Closure;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Exception\InvalidRequestException;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\ResponseFactory;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Traversable;
use function http_build_query;
use function in_array;
use function is_iterable;
use function Safe\json_encode;
use function strpos;

class Client implements ClientInterface
{
    protected RequesterInterface $requester;

    /** @var iterable<DecoratorInterface> */
    protected $decorators;

    protected ?ResponseFactory $responseFactory = null;

    /**
     * @param iterable<DecoratorInterface>|null $requestDecorators
     */
    public function __construct(RequesterInterface $requester, ?iterable $requestDecorators = null)
    {
        $this->requester = $requester;
        $this->decorators = $requestDecorators ?? [];
    }

    /** {@inheritdoc} */
    public function delete(string $path, ?array $headers = null): ResponseInterface
    {
        return $this->request('DELETE', $path, null, $headers);
    }

    /** {@inheritdoc} */
    public function get(string $path, ?array $headers = null): ResponseInterface
    {
        return $this->request('GET', $path, null, $headers);
    }

    /**
     * Performs a merge-patch request to the API service using the PATCH method.
     *
     * @param array|string|resource|Closure<string>|Traversable<string>|null $requestData
     * @param string[]|string[][]|null                                       $headers
     *
     * @phpstan-param array|string|resource|Closure(): string|Traversable<string>|null $requestData
     */
    public function mergePatch(string $path, $requestData = null, ?array $headers = null): ResponseInterface
    {
        $headers = new HeaderBag($headers ?? []);
        $headers->set('content-type', 'application/merge-patch+json');

        return $this->request('PATCH', $path, $requestData, $headers->all());
    }

    /** {@inheritdoc} */
    public function post(string $path, $requestData = null, ?array $headers = null): ResponseInterface
    {
        return $this->request('POST', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function put(string $path, $requestData = null, ?array $headers = null): ResponseInterface
    {
        return $this->request('PUT', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function patch(string $path, $requestData = null, ?array $headers = null): ResponseInterface
    {
        return $this->request('PATCH', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function request(string $method, string $path, $requestData = null, ?array $headers = null): ResponseInterface
    {
        if (in_array($method, ['GET', 'HEAD', 'DELETE'])) {
            $requestData = null;
        }

        $headerBag = new HeaderBag($headers ?? []);
        $headerBag->set('Accept', 'application/json', false);

        $request = new Request($method, $path, $headerBag->all(), $requestData);

        foreach ($this->decorators as $decorator) {
            $request = $decorator->decorate($request);
        }

        $body = $request->getBody();
        if (is_iterable($body)) {
            $contentType = $headerBag->get('Content-type');
            if ($contentType === null) {
                $contentType = 'application/json';
                $headerBag = new HeaderBag($request->getHeaders() ?? []);
                $headerBag->set('Content-type', $contentType);
                $request = new Request($request->getMethod(), $request->getUrl(), $headerBag->all(), $request->getBody());
            }

            if (strpos($contentType, 'application/json') === 0) {
                $body = json_encode($body);
            } else {
                $body = http_build_query($body);
            }
        }

        $response = $this->requester->request(
            $request->getMethod(),
            $request->getUrl(),
            $request->getHeaders(),
            $body
        );

        self::filterResponse($response);

        return $response;
    }

    public function setResponseFactory(?ResponseFactory $factory): self
    {
        $this->responseFactory = $factory;

        return $this;
    }

    protected static function filterResponse(ResponseInterface $response): void
    {
        if ($response instanceof BadResponse) {
            throw new BadRequestException($response);
        }

        if ($response instanceof InvalidResponse) {
            throw new InvalidRequestException($response);
        }
    }
}
