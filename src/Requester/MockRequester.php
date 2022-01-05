<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use RuntimeException;
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
     * {@inheritdoc}
     */
    public function request(string $method, string $uri, array $headers, $requestData = null, bool $lazy = false, ?callable $filter = null): ResponseInterface
    {
        $response = array_shift($this->responses);
        if ($response === null) {
            throw new RuntimeException('Empty response list');
        }

        if ($filter !== null) {
            $filter($response);
        }

        return $response;
    }
}
