<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Storage;

use DateTime;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Storage\AbstractStorage;
use Solido\Atlante\Storage\Item;
use TypeError;

use function serialize;

class AbstractStorageTest extends TestCase
{
    private ConcreteStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ConcreteStorage();
    }

    public function testDefaultLifetime(): void
    {
        self::assertEquals(0, $this->storage->getDefaultLifetime());
    }

    public function testClear(): void
    {
        self::assertFalse($this->storage->clear());
    }

    public function testGet(): void
    {
        $item = $this->storage->getItem('foo');
        self::assertTrue($item->isHit());
        self::assertEquals(42, $item->get());
    }

    public function testSaveOnAlreadyExpiredItemDoesNotCallDoSave(): void
    {
        $item = $this->storage->getItem('bar');
        $item->set(42);
        $item->expiresAt(new DateTime('@0'));

        self::assertTrue($this->storage->save($item));
        self::assertFalse($this->storage->doSaveCalled);
        self::assertTrue($this->storage->doDeleteCalled);
    }

    public function testItemDefaultLifeTime(): void
    {
        $item = $this->storage->getItem('bar');
        $item->expiresAt(null);
        self::assertNull($this->storage->getExpiration($item));

        $item->expiresAfter(null);
        self::assertNull($this->storage->getExpiration($item));
    }

    public function testItemExpiresAfterShouldThrowOnInvalidArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Expiration date must be an integer, a DateInterval or null, "array" given.');

        $item = $this->storage->getItem('bar');
        $item->expiresAfter([]);
    }
}

class ConcreteStorage extends AbstractStorage
{
    public bool $doSaveCalled = false;
    public bool $doDeleteCalled = false;

    public function hasItem(string $key): bool
    {
        return $key === 'foo';
    }

    public function deleteItem(string $key): bool
    {
        $this->doDeleteCalled = true;

        return true;
    }

    protected function doGetItem(string $key): mixed
    {
        if ($key === 'foo') {
            return serialize(42);
        }

        return null;
    }

    protected function doSave(string $key, string $value, ?float $expiry): bool
    {
        $this->doSaveCalled = true;

        return true;
    }

    public function getDefaultLifetime(?Item $item = null): int
    {
        $getDefaultLifetime = (fn () => $this->getDefaultLifetime)->bindTo($this, AbstractStorage::class)();
        $item ??= (fn () => $this->createCacheItem)->bindTo($this, AbstractStorage::class)()('key', null, false);

        return $getDefaultLifetime($item);
    }

    public function getExpiration(Item $item): ?float
    {
        $getExpiration = (fn () => $this->getExpiration)->bindTo($this, AbstractStorage::class)();

        return $getExpiration($item);
    }
}
