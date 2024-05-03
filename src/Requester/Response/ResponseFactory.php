<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Solido\Atlante\Http\HeaderBag;
use TypeError;

use function get_debug_type;
use function sprintf;

class ResponseFactory extends AbstractResponseFactory
{
    public function fromResponse(object $response, callable|null $filter = null): ResponseInterface
    {
        if (! $response instanceof PsrResponseInterface) {
            throw new TypeError(sprintf('Argument 1 passed to %s must be an instance of %s, %s passed.', __METHOD__, PsrResponseInterface::class, get_debug_type($response)));
        }

        $builder = function () use ($response, $filter): ResponseInterface {
            $response = $this->makeResponse(
                $response->getStatusCode(),
                new HeaderBag($response->getHeaders()),
                (string) $response->getBody(),
            );

            if ($filter !== null) {
                $filter($response);
            }

            return $response;
        };

        return new LazyResponse($builder);
    }
}
