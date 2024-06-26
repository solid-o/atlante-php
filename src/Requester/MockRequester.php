<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use RuntimeException;
use Solido\Atlante\Requester\Response\LazyResponse;
use Solido\Atlante\Requester\Response\ResponseInterface;

use function array_shift;

final class MockRequester implements RequesterInterface
{
    /** @var ResponseInterface[] */
    private array $responses = [];

    /**
     * Schedule Responses in advance.
     */
    public function foresee(ResponseInterface ...$responses): self
    {
        $this->responses = $responses;

        return $this;
    }

    /**
     * Returns a scheduled Response from a FIFO queue.
     *
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $headers, mixed $requestData = null, callable|null $filter = null): ResponseInterface
    {
        $response = array_shift($this->responses);
        if ($response === null) {
            throw new RuntimeException('Empty response list');
        }

        return new LazyResponse(static function () use ($filter, $response): ResponseInterface {
            if ($filter !== null) {
                $filter($response);
            }

            return $response;
        });
    }
}
