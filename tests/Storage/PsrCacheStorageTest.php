<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Storage;

use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Storage\AbstractStorage;
use Solido\Atlante\Storage\PsrCacheStorage;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function microtime;
use function mt_rand;
use function sleep;

class PsrCacheStorageTest extends TestCase
{
    private PsrCacheStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new PsrCacheStorage(new ArrayAdapter());
    }

    public function testShouldThrowOnInvalidDefaultLifetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PsrCacheStorage(new ArrayAdapter(), -1);
    }

    public function testGet(): void
    {
        self::assertTrue($this->storage->clear());
        $value = mt_rand();

        $item = $this->storage->getItem('foo');
        $item->set($value);
        $this->storage->save($item);

        $item = $this->storage->getItem('foo');
        self::assertSame($value, $item->get());
    }

    public function testDefaultLifeTime(): void
    {
        $cache = new PsrCacheStorage(new ArrayAdapter(), 3);

        $item = $cache->getItem('key.dlt');
        $item->set('value');
        $cache->save($item);
        sleep(1);

        $item = $cache->getItem('key.dlt');
        self::assertTrue($item->isHit());

        sleep(3);
        $item = $cache->getItem('key.dlt');
        self::assertFalse($item->isHit());

        $item->expiresAfter(null);
        $now = microtime(true);
        $expiration = (fn () => ($this->getExpiration)($item))->bindTo($cache, AbstractStorage::class)();
        self::assertEqualsWithDelta($now + 3, $expiration, 1.0);

        $cache = new PsrCacheStorage(new ArrayAdapter());
        $item = $cache->getItem('test');
        $defaultLifetime = (fn () => ($this->getDefaultLifetime)($item))->bindTo($cache, AbstractStorage::class)();
        self::assertEquals(0, $defaultLifetime);

        $item->expiresAfter(null);
        $expiration = (fn () => ($this->getExpiration)($item))->bindTo($cache, AbstractStorage::class)();
        self::assertNull($expiration);
    }

    public function testExpiration(): void
    {
        $this->storage->save($this->storage->getItem('k1')->set('v1')->expiresAfter(2));
        $this->storage->save($this->storage->getItem('k2')->set('v2')->expiresAfter(new DateInterval('P1Y')));

        sleep(3);
        $item = $this->storage->getItem('k1');
        self::assertFalse($item->isHit());
        self::assertNull($item->get(), "Item's value must be null when isHit() is false.");

        $item = $this->storage->getItem('k2');
        self::assertTrue($item->isHit());
        self::assertSame('v2', $item->get());
    }
}
