<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Closure;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;
use function is_callable;
use function is_iterable;
use function is_resource;
use function is_string;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class BodyConverterDecorator implements DecoratorInterface
{
    public function decorate(Request $request): Request
    {
        $body = $request->getBody();
        $headers = new HeaderBag($request->getHeaders());

        $body = $body === null || is_string($body) ? $body : function () use ($body, $headers) {
            $body = $this->prepare($body);

            if (! is_string($body)) {
                $body = json_encode($body, JSON_THROW_ON_ERROR);
                $headers->set('content-type', 'application/json', true);
            }

            return $body;
        };

        return new Request($request->getMethod(), $request->getUrl(), $headers->all(), $body);
    }

    /**
     * @param array|string|resource|Closure|iterable<string>|null $body
     *
     * @return array<mixed, mixed>|string|null
     *
     * @phpstan-param array|string|resource|Closure(): string|iterable<string>|null $body
     */
    private function prepare($body)
    {
        if ($body === null) {
            return null;
        }

        if (is_callable($body)) {
            return $this->prepare($body());
        }

        if (is_resource($body)) {
            return (string) $body;
        }

        if (is_iterable($body)) {
            $iterated = [];
            foreach ($body as $k => $field) {
                $iterated[$k] = $this->prepare($field);
            }

            $body = $iterated;
        }

        return $body;
    }
}
