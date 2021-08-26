<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http;

use Solido\Atlante\Http\HeaderBag;
use PHPUnit\Framework\TestCase;

class HeaderBagTest extends TestCase
{
    public function testConstructor(): void
    {
        $bag = new HeaderBag(['foo' => 'bar']);
        self::assertTrue($bag->has('foo'));
    }

    public function testToStringNull(): void
    {
        $bag = new HeaderBag();
        self::assertEquals('', $bag->__toString());
    }

    public function testToStringNotNull(): void
    {
        $bag = new HeaderBag(['foo' => 'bar']);
        self::assertEquals("Foo: bar\r\n", $bag->__toString());
    }

    public function testToStringOrdered(): void
    {
        $bag = new HeaderBag(['foo' => 'bar', 'and-header' => 'first']);
        self::assertEquals("And-Header: first\r\nFoo:        bar\r\n", $bag->__toString());
    }

    public function testKeys(): void
    {
        $bag = new HeaderBag(['foo' => 'bar']);
        $keys = $bag->keys();
        self::assertEquals('foo', $keys[0]);
    }

    public function testGetDate(): void
    {
        $bag = new HeaderBag(['foo' => 'Tue, 4 Sep 2012 20:00:00 +0200']);
        $headerDate = $bag->getDate('foo');
        self::assertInstanceOf(\DateTime::class, $headerDate);
    }

    public function testGetDateNull(): void
    {
        $bag = new HeaderBag(['foo' => null]);
        $headerDate = $bag->getDate('foo');
        self::assertNull($headerDate);
    }

    public function testGetDateException(): void
    {
        $this->expectException(\RuntimeException::class);
        $bag = new HeaderBag(['foo' => 'Tue']);
        $bag->getDate('foo');
    }

    public function testGetCacheControlHeader(): void
    {
        $bag = new HeaderBag();
        $bag->addCacheControlDirective('public', '#a');
        self::assertTrue($bag->hasCacheControlDirective('public'));
        self::assertEquals('#a', $bag->getCacheControlDirective('public'));
    }

    public function testAll(): void
    {
        $bag = new HeaderBag(['foo' => 'bar']);
        self::assertEquals(['foo' => ['bar']], $bag->all(), '->all() gets all the input');

        $bag = new HeaderBag(['FOO' => 'BAR']);
        self::assertEquals(['foo' => ['BAR']], $bag->all(), '->all() gets all the input key are lower case');
    }

    public function testReplace(): void
    {
        $bag = new HeaderBag(['foo' => 'bar']);

        $bag->replace(['NOPE' => 'BAR']);
        self::assertEquals(['nope' => ['BAR']], $bag->all(), '->replace() replaces the input with the argument');
        self::assertFalse($bag->has('foo'), '->replace() overrides previously set the input');
    }

    public function testGet(): void
    {
        $bag = new HeaderBag(['foo' => 'bar', 'fuzz' => 'bizz']);
        self::assertEquals('bar', $bag->get('foo'), '->get return current value');
        self::assertEquals('bar', $bag->get('FoO'), '->get key in case insensitive');
        self::assertEquals(['bar'], $bag->all('foo'), '->get return the value as array');

        // defaults
        self::assertNull($bag->get('none'), '->get unknown values returns null');
        self::assertEquals('default', $bag->get('none', 'default'), '->get unknown values returns default');
        self::assertEquals([], $bag->all('none'), '->get unknown values returns an empty array');

        $bag->set('foo', 'bor', false);
        self::assertEquals('bar', $bag->get('foo'), '->get return first value');
        self::assertEquals(['bar', 'bor'], $bag->all('foo'), '->get return all values as array');

        $bag->set('baz', null);
        self::assertNull($bag->get('baz', 'nope'), '->get return null although different default value is given');
    }

    public function testAdd(): void
    {
        $bag = new HeaderBag(['foo' => 'bar', 'fuzz' => 'bizz']);
        $bag->add(['foo' => 'baz', 'fizz' => 'bar']);

        self::assertEquals(['foo' => ['baz'], 'fuzz' => ['bizz'], 'fizz' => ['bar']], $bag->all());
    }

    public function testSet(): void
    {
        $bag = new HeaderBag(['foo' => 'bar', 'fuzz' => 'bizz']);
        $bag->set('foo', 'baz');
        self::assertEquals(['foo' => ['baz'], 'fuzz' => ['bizz']], $bag->all());

        $bag->set('foo', 'bar', true);
        self::assertEquals(['foo' => ['bar'], 'fuzz' => ['bizz']], $bag->all());

        $bag->set('foo', 'baz', false);
        self::assertEquals(['foo' => ['bar', 'baz'], 'fuzz' => ['bizz']], $bag->all());

        $bag->set('foo', ['fiz', 'fuz'], true);
        self::assertEquals(['foo' => ['fiz', 'fuz'], 'fuzz' => ['bizz']], $bag->all());

        $bag->set('foo', ['foobar'], false);
        self::assertEquals(['foo' => ['fiz', 'fuz', 'foobar'], 'fuzz' => ['bizz']], $bag->all());
    }

    public function testSetAssociativeArray(): void
    {
        $bag = new HeaderBag();
        $bag->set('foo', ['bad-assoc-index' => 'value']);
        self::assertSame('value', $bag->get('foo'));
        self::assertSame(['value'], $bag->all('foo'), 'assoc indices of multi-valued headers are ignored');
    }

    public function testContains(): void
    {
        $bag = new HeaderBag(['foo' => 'bar', 'fuzz' => 'bizz']);
        self::assertTrue($bag->contains('foo', 'bar'), '->contains first value');
        self::assertTrue($bag->contains('fuzz', 'bizz'), '->contains second value');
        self::assertFalse($bag->contains('nope', 'nope'), '->contains unknown value');
        self::assertFalse($bag->contains('foo', 'nope'), '->contains unknown value');

        // Multiple values
        $bag->set('foo', 'bor', false);
        self::assertTrue($bag->contains('foo', 'bar'), '->contains first value');
        self::assertTrue($bag->contains('foo', 'bor'), '->contains second value');
        self::assertFalse($bag->contains('foo', 'nope'), '->contains unknown value');
    }

    public function testCacheControlDirectiveAccessors(): void
    {
        $bag = new HeaderBag();
        $bag->addCacheControlDirective('public');

        self::assertTrue($bag->hasCacheControlDirective('public'));
        self::assertTrue($bag->getCacheControlDirective('public'));
        self::assertEquals('public', $bag->get('cache-control'));

        $bag->addCacheControlDirective('max-age', 10);
        self::assertTrue($bag->hasCacheControlDirective('max-age'));
        self::assertEquals(10, $bag->getCacheControlDirective('max-age'));
        self::assertEquals('max-age=10, public', $bag->get('cache-control'));

        $bag->removeCacheControlDirective('max-age');
        self::assertFalse($bag->hasCacheControlDirective('max-age'));
    }

    public function testCacheControlDirectiveParsing(): void
    {
        $bag = new HeaderBag(['cache-control' => 'public, max-age=10']);
        self::assertTrue($bag->hasCacheControlDirective('public'));
        self::assertTrue($bag->getCacheControlDirective('public'));

        self::assertTrue($bag->hasCacheControlDirective('max-age'));
        self::assertEquals(10, $bag->getCacheControlDirective('max-age'));

        $bag->addCacheControlDirective('s-maxage', 100);
        self::assertEquals('max-age=10, public, s-maxage=100', $bag->get('cache-control'));
    }

    public function testCacheControlDirectiveParsingQuotedZero(): void
    {
        $bag = new HeaderBag(['cache-control' => 'max-age="0"']);
        self::assertTrue($bag->hasCacheControlDirective('max-age'));
        self::assertEquals(0, $bag->getCacheControlDirective('max-age'));
    }

    public function testCacheControlDirectiveOverrideWithReplace(): void
    {
        $bag = new HeaderBag(['cache-control' => 'private, max-age=100']);
        $bag->replace(['cache-control' => 'public, max-age=10']);
        self::assertTrue($bag->hasCacheControlDirective('public'));
        self::assertTrue($bag->getCacheControlDirective('public'));

        self::assertTrue($bag->hasCacheControlDirective('max-age'));
        self::assertEquals(10, $bag->getCacheControlDirective('max-age'));
    }

    public function testCacheControlClone(): void
    {
        $headers = ['foo' => 'bar'];
        $bag1 = new HeaderBag($headers);
        $bag2 = new HeaderBag($bag1->all());

        self::assertEquals($bag1->all(), $bag2->all());
    }

    public function testGetIterator(): void
    {
        $headers = ['foo' => 'bar', 'hello' => 'world', 'third' => 'charm'];
        $headerBag = new HeaderBag($headers);

        $i = 0;
        foreach ($headerBag as $key => $val) {
            ++$i;
            self::assertEquals([$headers[$key]], $val);
        }

        self::assertEquals(\count($headers), $i);
    }

    public function testCount(): void
    {
        $headers = ['foo' => 'bar', 'HELLO' => 'WORLD'];
        $headerBag = new HeaderBag($headers);

        self::assertCount(\count($headers), $headerBag);
    }
}
