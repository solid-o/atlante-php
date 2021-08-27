<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Closure;
use InvalidArgumentException;
use Solido\Atlante\Http\HeaderBag;
use Traversable;

use function method_exists;
use function Safe\sprintf;

/**
 * @property-read array|string|resource|Traversable|Closure $body
 * @property-read string                                    $url
 * @property-read string                                    $method
 * @property-read string[]|string[][]                       $headers
 */
class Request
{
    /**
     * @var array|string|resource|Closure<string>|iterable<string>|null
     * @phpstan-var array|string|resource|Closure(): string|iterable<string>|null
     */
    private $body;

    private HeaderBag $headers;
    private string $method;
    private string $url;

    /**
     * @param array|string|resource|Closure<string>|iterable<string>|null $body
     * @param string[]|string[][]|null                                       $headers
     * @phpstan-param array|string|resource|Closure(): string|iterable<string>|null $body
     */
    public function __construct(string $method, string $url, ?array $headers = null, $body = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = new HeaderBag($headers ?? []);
        $this->body = $body;
    }

    /**
     * @return array|string|resource|Closure<string>|iterable<string>|null
     * @phpstan-return array|string|resource|Closure(): string|iterable<string>|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string[]|string[][]
     */
    public function getHeaders(): array
    {
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

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        $method = sprintf('get%s', $name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException('Undefined property: ' . self::class . '::' . $name);
    }
}
