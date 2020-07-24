<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Exception\InvalidRequestException;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\ResponseFactory;
use Solido\Atlante\Requester\Response\ResponseInterface;
use function assert;
use function end;
use function in_array;
use function is_callable;
use function is_iterable;

class Client implements ClientInterface
{
    protected RequesterInterface $requester;

    /** @var iterable<DecoratorInterface> */
    protected $decorators;

    protected ?ResponseFactory $responseFactory = null;

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

        $body = $request->getBody();
        if ((is_iterable($body) || is_callable($body)) && (empty($this->decorators) || ! (end($this->decorators) instanceof BodyConverterDecorator))) {
            $decorator = new BodyConverterDecorator();
            $request = $decorator->decorate($request);
        }

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
