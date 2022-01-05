<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
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
    public function request(string $method, string $path, $requestData = null, ?array $headers = null, bool $throw = true, bool $lazy = false): ResponseInterface
    {
        if (in_array($method, ['GET', 'HEAD', 'DELETE'])) {
            $requestData = null;
        }

        $request = new Request($method, $path, $headers ?? [], $requestData);

        foreach ($this->decorators as $decorator) {
            $request = $decorator->decorate($request);
        }

        $request = self::normalizeRequestBody($request);
        assert(! is_iterable($request->getBody()));

        $headerBag = new HeaderBag($request->getHeaders());
        if (! $headerBag->has('Accept')) {
            $headerBag->set('Accept', 'application/json');
        }

        $filter = $throw ? static fn (ResponseInterface $response) => static::filterResponse($response) : null;

        return $this->requester->request(
            $request->getMethod(),
            $request->getUrl(),
            $headerBag->all(),
            $request->getBody(),
            $lazy,
            $filter,
        );
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
            return (new BodyConverterDecorator())->decorate($request);
        };

        if (is_callable($body)) {
            $body = Closure::fromCallable($body);
            $refl = new ReflectionFunction($body);
            $returnType = $refl->getReturnType();
            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'string') {
                // if Closure will return a string (accepted by Requesters) return Request untouched
                return new Request($request->getMethod(), $request->getUrl(), $request->getHeaders(), $body);
            }

            return $doNormalizeBody($request);
        }

        if (is_iterable($body)) {
            return $doNormalizeBody($request);
        }

        throw new TypeError(sprintf('Given request body has to be a string, a stream resource, a function that returns a string, a generator yielding strings or an iterable of strings, "%s" given', get_debug_type($body)));
    }

    protected static function filterResponse(ResponseInterface $response): void
    {
        if ($response instanceof BadResponse) {
            throw new BadRequestException($response);
        }

        if ($response instanceof AccessDeniedResponse) {
            throw new AccessDeniedException($response);
        }

        if ($response instanceof NotFoundResponse) {
            throw new NotFoundException($response);
        }

        if ($response instanceof InvalidResponse) {
                throw new InvalidRequestException($response);
        }
    }
}
