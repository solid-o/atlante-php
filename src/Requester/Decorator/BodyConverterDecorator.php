<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Closure;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;
use UnexpectedValueException;
use function http_build_query;
use function is_callable;
use function is_iterable;
use function is_resource;
use function is_string;
use function Safe\json_encode;
use function Safe\sprintf;
use function strpos;

class BodyConverterDecorator implements DecoratorInterface
{
    public function decorate(Request $request): Request
    {
        $body = $request->getBody();
        $headers = new HeaderBag($request->getHeaders());

        $body = $body === null || is_string($body) ? $body : function () use ($body, $headers) {
            $body = $this->prepare($body);

            if (! is_string($body) && $body !== null) {
                $contentType = $headers->get('content-type');
                // add content-type if not specified
                if ($contentType === null) {
                    $contentType = 'application/json';
                    $headers->set('content-type', $contentType);
                }

                if (strpos($contentType, 'application/json') === 0) {
                    $body = json_encode($body);
                } elseif (strpos($contentType, 'application/x-www-form-urlencoded') === 0) {
                    $body = http_build_query($body);
                } else {
                    throw new UnexpectedValueException(
                        sprintf(
                            'Unable to convert Request content body: expected "application/json" or "application/x-www-form-urlencoded" `content-type` header, "%s" given',
                            $contentType
                        )
                    );
                }
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
