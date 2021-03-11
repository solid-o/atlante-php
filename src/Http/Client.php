<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Closure;
use ReflectionFunction;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Exception\AccessDeniedException;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Exception\InvalidRequestException;
use Solido\Atlante\Requester\Exception\NotFoundException;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\AccessDeniedResponse;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\NotFoundResponse;
use Solido\Atlante\Requester\Response\ResponseInterface;
use TypeError;

use function assert;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_callable;
use function is_iterable;
use function is_resource;
use function is_string;
use function Safe\sprintf;
use function stream_get_meta_data;

class Client implements ClientInterface
{
    protected RequesterInterface $requester;

    /** @var iterable<DecoratorInterface> */
    protected iterable $decorators;

    /**
     * @param iterable<DecoratorInterface>|null $requestDecorators Ordered list of Decorators
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

        $request = self::normalizeRequestBody($request);
        assert(! is_iterable($request->getBody()));

        $response = $this->requester->request(
            $request->getMethod(),
            $request->getUrl(),
            $request->getHeaders(),
            $request->getBody()
        );

        self::filterResponse($response);

        return $response;
    }

    /**
     * Convert Request body to null|string|resource|\Closure(): string
     */
    protected static function normalizeRequestBody(Request $request): Request
    {
        $body = $request->getBody();

        if ($body === null || is_string($body) || (is_resource($body) && is_array(@stream_get_meta_data($body)))) {
            return $request;
        }

        $doNormalizeBody = static function (Request $request): Request {
            $decorator = new BodyConverterDecorator();

            return $decorator->decorate($request);
        };

        if (is_callable($body)) {
            if (! $body instanceof Closure) {
                $body = Closure::fromCallable($body);
            }

            $refl = new ReflectionFunction($body);
            $returnType = $refl->getReturnType();
            if ($returnType !== null && (string) $returnType === 'string') {
                // if Closure will return a string (accepted by Requesters) return Request untouched
                return $request;
            }

            return $doNormalizeBody($request);
        }

        if (is_iterable($body)) {
            return $doNormalizeBody($request);
        }

        throw new TypeError(sprintf('Given request body has to be a string, a stream resource, a function that returns a string, a generator yielding strings or an iterable of strings, "%s" given', __METHOD__, get_debug_type($body)));
    }

    protected static function filterResponse(ResponseInterface $response): void
    {
        switch (true) {
            case $response instanceof BadResponse:
                throw new BadRequestException($response);

            case $response instanceof AccessDeniedResponse:
                throw new AccessDeniedException($response);

            case $response instanceof NotFoundResponse:
                throw new NotFoundException($response);

            case $response instanceof InvalidResponse:
                throw new InvalidRequestException($response);
        }
    }
}
