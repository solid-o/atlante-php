<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use Solido\Atlante\Exception\AccessDeniedHttpException;
use Solido\Atlante\Exception\BadRequestHttpException;
use Solido\Atlante\Exception\HttpException;
use Solido\Atlante\Exception\NotFoundHttpException;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response;
use function assert;
use function in_array;
use function is_iterable;

class Client implements ClientInterface
{
    /** @var RequesterInterface */
    protected $requester;

    /** @var iterable<DecoratorInterface> */
    protected $decorators;

    /**
     * @param iterable<DecoratorInterface>|null $requestDecorators
     */
    public function __construct(RequesterInterface $requester, ?iterable $requestDecorators = null)
    {
        $this->requester = $requester;
        $this->decorators = $requestDecorators ?? [];
    }

    /** {@inheritdoc} */
    public function delete(string $path, ?array $headers = null): Response
    {
        return $this->request('DELETE', $path, null, $headers);
    }

    /** {@inheritdoc} */
    public function get(string $path, ?array $headers = null): Response
    {
        return $this->request('GET', $path, null, $headers);
    }

    /** {@inheritdoc} */
    public function post(string $path, $requestData = null, ?array $headers = null): Response
    {
        return $this->request('POST', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function put(string $path, $requestData = null, ?array $headers = null): Response
    {
        return $this->request('PUT', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function patch(string $path, $requestData = null, ?array $headers = null): Response
    {
        return $this->request('PATCH', $path, $requestData, $headers);
    }

    /** {@inheritdoc} */
    public function request(string $method, string $path, $requestData = null, ?array $headers = null): Response
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
        assert(! is_iterable($body));

        $response = $this->requester->request(
            $request->getMethod(),
            $request->getUrl(),
            $request->getHeaders(),
            $body
        );

        self::filterResponse($response);

        return $response;
    }

    protected static function filterResponse(Response $response): void
    {
        $statusCode = $response->getStatus();
        if (200 <= $statusCode && 300 > $statusCode) {
            return;
        }

        switch ($statusCode) {
            case 404:
                throw new NotFoundHttpException();
            case 400:
                throw new BadRequestHttpException();
            case 403:
                throw new AccessDeniedHttpException('Forbidden');
            default:
                throw new HttpException($statusCode, 'Response is not ok: status code ' . $statusCode);
        }
    }
}
