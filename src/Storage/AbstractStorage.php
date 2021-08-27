<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use Closure;
use DateInterval;
use InvalidArgumentException;
use Safe\DateTime;

use function Safe\sprintf;
use function serialize;
use function unserialize;

abstract class AbstractStorage implements StorageInterface
{
    private Closure $createCacheItem;
    private Closure $getExpiration;
    private Closure $getDefaultLifetime;

    public function __construct(int $defaultLifetime = 0)
    {
        if ($defaultLifetime < 0) {
            throw new InvalidArgumentException('Storage default lifetime should be equal or greater than 0');
        }

        $this->createCacheItem = Closure::bind(
            static function ($key, $value, $isHit) use ($defaultLifetime) {
                $item = new Item();
                $item->key = $key;
                $item->value = $value;
                $item->isHit = $isHit;
                $item->defaultLifetime = $defaultLifetime;

                return $item;
            },
            null,
            Item::class
        );

        $this->getExpiration = Closure::bind(
            static function (Item $item) {
                return $item->expiry;
            },
            null,
            Item::class
        );

        $this->getDefaultLifetime = Closure::bind(
            static function (Item $item) {
                return $item->defaultLifetime;
            },
            null,
            Item::class
        );
    }

    public function getItem(string $key): ItemInterface
    {
        $value = null;
        $isHit = $this->hasItem($key);

        if ($isHit) {
            $value = unserialize($this->doGetItem($key), ['allowed_classes' => true]);
        }

        $r = $this->createCacheItem;

        return $r($key, $value, $isHit);
    }

    abstract public function hasItem(string $key): bool;

    public function clear(): bool
    {
        return false;
    }

    abstract public function deleteItem(string $key): bool;

    public function save(ItemInterface $item): bool
    {
        $key = $item->getKey();
        $expiry = ($this->getExpiration)($item);

        if ($expiry !== null && DateTime::createFromFormat('U.u', sprintf('%.3f', $expiry)) < new DateTime()) {
            $this->deleteItem($key);

            return true;
        }

        $value = $item->get();
        $value = serialize($value);

        $defaultLifetime = ($this->getDefaultLifetime)($item);
        if ($expiry === null && 0 < $defaultLifetime) {
            $expiry = (float) (new DateTime())
                ->add(new DateInterval('PT' . $defaultLifetime . 'S'))
                ->format('U.u');
        }

        return $this->doSave($key, $value, $expiry);
    }

    /**
     * Gets an item from the storage.
     *
     * @return mixed
     */
    abstract protected function doGetItem(string $key);

    /**
     * Saves an item on the storage.
     */
    abstract protected function doSave(string $key, string $value, ?float $expiry): bool;
}
