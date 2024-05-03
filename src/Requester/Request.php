<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Closure;
use InvalidArgumentException;
use Solido\Atlante\Http\HeaderBag;
use Traversable;

use function method_exists;
use function sprintf;

/**
 * @property-read array|string|resource|Traversable|Closure $body
 * @property-read string $url
 * @property-read string $method
 * @property-read string[]|string[][] $headers
 */
class Request
{
    private readonly HeaderBag $headers;

    /**
     * @param array<array-key, mixed>|string|resource|Closure|iterable<string>|null $body
     * @param array<string, string|array<string|null>|null> $headers
     * @phpstan-param array<array-key, mixed>|string|resource|Closure(): string|iterable<string>|null $body
     */
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        array|null $headers = null,
        private readonly mixed $body = null,
    ) {
        $this->headers = new HeaderBag($headers ?? []);
    }

    /**
     * @return array<array-key, mixed>|string|resource|Closure|iterable<string>|null
     * @phpstan-return array<array-key, mixed>|string|resource|Closure(): string|iterable<string>|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /** @return array<string, string|array<string|null>|null> */
    public function getHeaders(): array
    {
        /** @phpstan-ignore-next-line */
        return $this->headers->all();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function __get(string $name): mixed
    {
        $method = sprintf('get%s', $name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException('Undefined property: ' . self::class . '::' . $name);
    }
}
