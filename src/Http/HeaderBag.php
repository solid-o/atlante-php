<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use ArrayIterator;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use RuntimeException;
use Safe\DateTime;
use Safe\Exceptions\DatetimeException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function Safe\ksort;
use function Safe\sprintf;
use function strtr;
use function ucwords;

use const DATE_RFC2822;

/**
 * HeaderBag is a container for HTTP headers.
 */
class HeaderBag implements IteratorAggregate, Countable
{
    protected const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    /** @var array<string, string[]> */
    protected array $headers = [];

    /** @var array<string, mixed> */
    protected array $cacheControl = [];

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns the headers as a string.
     *
     * @return string The headers
     */
    public function __toString(): string
    {
        $headers = $this->all();
        if (! $headers) {
            return '';
        }

        ksort($headers);
        $max = max(array_map('strlen', array_keys($headers))) + 1;
        $content = '';
        foreach ($headers as $name => $values) {
            $name = ucwords($name, '-');
            foreach ($values as $value) {
                $content .= sprintf('%-' . $max . "s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Returns the headers.
     *
     * @param string|null $key The name of the headers to return or null to get them all
     *
     * @return array<string, string[]>|string[] An array of headers
     */
    public function all(?string $key = null): array
    {
        if ($key !== null) {
            return $this->headers[strtr($key, self::UPPER, self::LOWER)] ?? [];
        }

        return $this->headers;
    }

    /**
     * Returns the header names.
     *
     * @return string[] An array of header names
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * Replaces the current HTTP headers by a new set.
     *
     * @param array<string, string|string[]> $headers
     */
    public function replace(array $headers = []): void
    {
        $this->headers = [];
        $this->add($headers);
    }

    /**
     * Adds new headers the current HTTP headers set.
     *
     * @param array<string, string|string[]> $headers
     */
    public function add(array $headers): void
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns a header value by name.
     *
     * @return string|null The first header value or default value
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $headers = $this->all($key);

        if (! $headers) {
            return $default;
        }

        if (! isset($headers[0])) {
            return null;
        }

        assert(is_string($headers[0]));

        return $headers[0];
    }

    /**
     * Sets a header by name.
     *
     * @param string|string[] $values  The value or an array of values
     * @param bool            $replace Whether to replace the actual value or not (true by default)
     */
    public function set(string $key, $values, bool $replace = true): void
    {
        $key = strtr($key, self::UPPER, self::LOWER);

        if (is_array($values)) {
            $values = array_values($values);

            if ($replace === true || ! isset($this->headers[$key])) {
                $this->headers[$key] = $values;
            } else {
                $this->headers[$key] = array_merge($this->headers[$key], $values);
            }
        } else {
            if ($replace === true || ! isset($this->headers[$key])) {
                $this->headers[$key] = [$values];
            } else {
                $this->headers[$key][] = $values;
            }
        }

        if ($key !== 'cache-control') {
            return;
        }

        $this->cacheControl = $this->parseCacheControl(implode(', ', $this->headers[$key]));
    }

    /**
     * Returns true if the HTTP header is defined.
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists(strtr($key, self::UPPER, self::LOWER), $this->all());
    }

    /**
     * Returns true if the given HTTP header contains the given value.
     *
     * @return bool true if the value is contained in the header, false otherwise
     */
    public function contains(string $key, string $value): bool
    {
        return in_array($value, $this->all($key));
    }

    /**
     * Removes a header.
     */
    public function remove(string $key): void
    {
        $key = strtr($key, self::UPPER, self::LOWER);

        unset($this->headers[$key]);

        if ($key !== 'cache-control') {
            return;
        }

        $this->cacheControl = [];
    }

    /**
     * Returns the HTTP header value converted to a date.
     *
     * @return DateTimeInterface|null The parsed DateTime or the default value if the header does not exist
     *
     * @throws RuntimeException When the HTTP header is not parseable.
     */
    public function getDate(string $key, ?DateTime $default = null): ?DateTimeInterface
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }

        try {
            return DateTime::createFromFormat(DATE_RFC2822, $value);
        } catch (DatetimeException $e) {
            throw new RuntimeException(sprintf('The "%s" HTTP header is not parseable (%s).', $key, $value), 0, $e);
        }
    }

    /**
     * Adds a custom Cache-Control directive.
     *
     * @param mixed $value The Cache-Control directive value
     */
    public function addCacheControlDirective(string $key, $value = true): void
    {
        $this->cacheControl[$key] = $value;

        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Returns true if the Cache-Control directive is defined.
     *
     * @return bool true if the directive exists, false otherwise
     */
    public function hasCacheControlDirective(string $key): bool
    {
        return array_key_exists($key, $this->cacheControl);
    }

    /**
     * Returns a Cache-Control directive value by name.
     *
     * @return mixed|null The directive value if defined, null otherwise
     */
    public function getCacheControlDirective(string $key)
    {
        return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }

    /**
     * Removes a Cache-Control directive.
     */
    public function removeCacheControlDirective(string $key): void
    {
        unset($this->cacheControl[$key]);

        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Returns an iterator for headers.
     *
     * @return ArrayIterator An \ArrayIterator instance
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->headers);
    }

    /**
     * Returns the number of headers.
     *
     * @return int The number of headers
     */
    public function count(): int
    {
        return count($this->headers);
    }

    protected function getCacheControlHeader(): string
    {
        ksort($this->cacheControl);

        return HeaderUtils::toString($this->cacheControl, ',');
    }

    /**
     * Parses a Cache-Control HTTP header.
     *
     * @return array<string, mixed> An array representing the attribute values
     */
    protected function parseCacheControl(string $header): array
    {
        $parts = HeaderUtils::split($header, ',=');

        return HeaderUtils::combine($parts);
    }
}
