<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;
use UnexpectedValueException;
use function get_debug_type;
use function http_build_query;
use function is_array;
use function is_callable;
use function is_iterable;
use function is_resource;
use function is_string;
use function Safe\json_encode;
use function Safe\sprintf;
use function stream_get_meta_data;
use function strpos;
use const PHP_QUERY_RFC1738;

/**
 * Ensure that the decorated Request has a string|null|\Closure<string>|resource<stream> body type.
 *
 * This decorator also changes Request headers accordingly if an iterable body is
 * given.
 */
class BodyConverterDecorator implements DecoratorInterface
{
    public function decorate(Request $request): Request
    {
        $body = $request->getBody();
        $headers = new HeaderBag($request->getHeaders());

        if ($body !== null && ! is_string($body)) {
            $doHandle = function () use ($body, $headers) {
                $body = $this->prepare($body);

                if (is_iterable($body)) {
                    $body = self::encodeIterable($body, $headers);
                }

                return $body;
            };

            if (is_callable($body)) {
                if (! $body instanceof Closure) {
                    $body = Closure::fromCallable($body);
                }

                $refl = new ReflectionFunction($body);
                $returnType = $refl->getReturnType();
                // @phpstan-ignore-next-line
                if ($returnType === null || $returnType->getName() !== 'string') {
                    $body = $doHandle;
                }
            } elseif (! is_resource($body) || ! is_array(@stream_get_meta_data($body))) {
                $body = $doHandle;
            }
        }

        return new Request($request->getMethod(), $request->getUrl(), $headers->all(), $body);
    }

    /**
     * @param iterable<string> $body
     */
    private static function encodeIterable($body, HeaderBag $headers): string
    {
        $contentType = $headers->get('content-type');
        // add content-type if not specified
        if ($contentType === null) {
            $contentType = 'application/json';
            $headers->set('content-type', $contentType);
        }

        if (strpos($contentType, 'application/json') === 0) {
            $body = json_encode($body);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') === 0) {
            $body = http_build_query($body, '', '&', PHP_QUERY_RFC1738);
        } else {
            throw new UnexpectedValueException(
                sprintf(
                    'Unable to convert Request content body: expected "application/json" or "application/x-www-form-urlencoded" `content-type` header, "%s" given',
                    $contentType
                )
            );
        }

        return $body;
    }

    /**
     * @param array|string|resource|Closure|iterable<string>|null $body
     *
     * @return array<mixed, mixed>|resource|string|Closure<string>|null
     *
     * @phpstan-param array|string|resource|Closure(): string|iterable<string>|null $body
     * @phpstan-return array<mixed, mixed>|resource|string|Closure(): string|null
     */
    private function prepare($body)
    {
        if ($body === null || is_string($body)) {
            return $body;
        }

        if (is_callable($body)) {
            return $this->prepare($body());
        }

        // if it's a valid stream resource
        if (is_resource($body) && is_array(@stream_get_meta_data($body))) {
            return $body;
        }

        if (is_iterable($body)) {
            $iterated = [];
            foreach ($body as $k => $field) {
                $iterated[$k] = $this->prepare($field);
            }

            return $iterated;
        }

        throw new InvalidArgumentException(sprintf('Argument #0 passed to %s has to be null, string, stream resource, iterable or callable, "%s" given', __METHOD__, get_debug_type($body)));
    }
}
