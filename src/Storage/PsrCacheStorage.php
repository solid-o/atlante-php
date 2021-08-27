<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use Psr\Cache\CacheItemPoolInterface;
use Safe\DateTime;

use function Safe\sprintf;

class PsrCacheStorage extends AbstractStorage
{
    private CacheItemPoolInterface $itemPool;

    public function __construct(CacheItemPoolInterface $itemPool, int $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);

        $this->itemPool = $itemPool;
    }

    public function clear(): bool
    {
        return $this->itemPool->clear();
    }

    public function hasItem(string $key): bool
    {
        return $this->itemPool->hasItem($key);
    }

    public function deleteItem(string $key): bool
    {
        return $this->itemPool->deleteItem($key);
    }

    /**
     * @inheritDoc
     */
    protected function doGetItem(string $key)
    {
        $item = $this->itemPool->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    protected function doSave(string $key, string $value, ?float $expiry): bool
    {
        $item = $this->itemPool->getItem($key);
        if ($expiry !== null) {
            $item->expiresAt(DateTime::createFromFormat('U.u', sprintf('%.3f', $expiry)));
        }

        return $this->itemPool->save($item->set($value));
    }
}
