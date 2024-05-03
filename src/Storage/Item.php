<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use DateInterval;
use DateTime;
use DateTimeInterface;
use TypeError;

use function get_debug_type;
use function is_int;
use function microtime;
use function sprintf;

class Item implements ItemInterface
{
    private mixed $value;

    /** @phpstan-ignore-next-line */
    private string $key;

    /** @phpstan-ignore-next-line */
    private bool $isHit;

    /** @phpstan-ignore-next-line */
    private float|null $expiry = null;

    /** @phpstan-ignore-next-line */
    private int $defaultLifetime;

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /** @inheritDoc */
    public function set($value): ItemInterface
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(DateTimeInterface|null $expiration): ItemInterface
    {
        if ($expiration === null) {
            $this->expiry = $this->defaultLifetime > 0 ? microtime(true) + $this->defaultLifetime : null;
        } else {
            $this->expiry = (float) $expiration->format('U.u');
        }

        return $this;
    }

    /** @inheritDoc */
    public function expiresAfter($time): ItemInterface
    {
        if ($time === null) {
            $this->expiry = $this->defaultLifetime > 0 ? microtime(true) + $this->defaultLifetime : null;
        } elseif ($time instanceof DateInterval) {
            /** @phpstan-ignore-next-line */
            $this->expiry = microtime(true) + (float) DateTime::createFromFormat('U', '0')->add($time)->format('U.u');
        } elseif (is_int($time)) {
            $this->expiry = $time + microtime(true);
        } else {
            throw new TypeError(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given.', get_debug_type($time)));
        }

        return $this;
    }
}
