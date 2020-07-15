<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use Safe\DateTime;
use function get_debug_type;
use function is_int;
use function microtime;
use function Safe\sprintf;

class Item implements ItemInterface
{
    /** @var mixed */
    private $value;
    private string $key;
    private bool $isHit;

    // @phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.WriteOnlyProperty
    private ?float $expiry = null;
    private int $defaultLifetime;

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function set($value): ItemInterface
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): ItemInterface
    {
        if ($expiration === null) {
            $this->expiry = $this->defaultLifetime > 0 ? microtime(true) + $this->defaultLifetime : null;
        } elseif ($expiration instanceof DateTimeInterface) {
            $this->expiry = (float) $expiration->format('U.u');
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must implement DateTimeInterface or be null, "%s" given.', get_debug_type($expiration)));
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time): ItemInterface
    {
        if ($time === null) {
            $this->expiry = $this->defaultLifetime > 0 ? microtime(true) + $this->defaultLifetime : null;
        } elseif ($time instanceof DateInterval) {
            $this->expiry = microtime(true) + (float) DateTime::createFromFormat('U', '0')->add($time)->format('U.u');
        } elseif (is_int($time)) {
            $this->expiry = $time + microtime(true);
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given.', get_debug_type($time)));
        }

        return $this;
    }
}
