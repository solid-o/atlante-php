<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;
use TypeError;

use function get_debug_type;
use function Safe\sprintf;

class SymfonyHttpClientResponseFactory extends AbstractResponseFactory
{
    public function fromResponse(object $response, bool $lazy = false, ?callable $filter = null): ResponseInterface
    {
        if (! $response instanceof SymfonyResponseInterface) {
            throw new TypeError(sprintf('Argument 1 passed to %s must be an instance of %s, %s passed.', __METHOD__, SymfonyResponseInterface::class, get_debug_type($response)));
        }

        $builder = function () use ($response, $filter): ResponseInterface {
            $response = $this->makeResponse(
                $response->getStatusCode(),
                new HeaderBag($response->getHeaders(false)),
                $response->getContent(false)
            );

            if ($filter !== null) {
                $filter($response);
            }

            return $response;
        };

        return $lazy ? new LazyResponse($builder) : $builder();
    }
}
