<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use DateInterval;
use DateTimeInterface;

interface ItemInterface
{
    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string The key string for this cache item.
     */
    public function getKey(): string;

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed The value corresponding to this cache item's key, or null if not found.
     */
    public function get(): mixed;

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit(): bool;

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value The serializable value to be stored.
     *
     * @return static The invoked object.
     */
    public function set(mixed $value): self;

    /**
     * Sets the expiration time for this cache item.
     *
     * @param DateTimeInterface|null $expiration The point in time after which the item MUST be considered expired.
     * If null is passed explicitly, a default value MAY be used. If none is set,
     * the value should be stored permanently or for as long as the
     * implementation allows.
     *
     * @return static The called object.
     */
    public function expiresAt(DateTimeInterface|null $expiration): self;

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|DateInterval|null $time The period of time from the present after which the item MUST be considered
     * expired. An integer parameter is understood to be the time in seconds until
     * expiration. If null is passed explicitly, a default value MAY be used.
     * If none is set, the value should be stored permanently or for as long as the
     * implementation allows.
     *
     * @return static The called object.
     */
    public function expiresAfter(int|DateInterval|null $time): self;
}
