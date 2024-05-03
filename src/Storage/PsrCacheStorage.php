<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

use DateTime;
use Psr\Cache\CacheItemPoolInterface;

use function sprintf;

class PsrCacheStorage extends AbstractStorage
{
    public function __construct(private CacheItemPoolInterface $itemPool, int $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);
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

    protected function doGetItem(string $key): mixed
    {
        $item = $this->itemPool->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    protected function doSave(string $key, string $value, float|null $expiry): bool
    {
        $item = $this->itemPool->getItem($key);
        if ($expiry !== null) {
            /** @phpstan-ignore-next-line */
            $item->expiresAt(DateTime::createFromFormat('U.u', sprintf('%.3f', $expiry)));
        }

        return $this->itemPool->save($item->set($value));
    }
}
