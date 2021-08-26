<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http;

use PHPUnit\Framework\TestCase;
use Solido\Atlante\Http\HeaderUtils;

class HeaderUtilsTest extends TestCase
{
    /**
     * @dataProvider provideHeaderToSplit
     */
    public function testSplit(array $expected, string $header, string $separator): void
    {
        self::assertSame($expected, HeaderUtils::split($header, $separator));
    }

    public function provideHeaderToSplit(): array
    {
        return [
            [['foo=123', 'bar'], 'foo=123,bar', ','],
            [['foo=123', 'bar'], 'foo=123, bar', ','],
            [[['foo=123', 'bar']], 'foo=123; bar', ',;'],
            [[['foo=123'], ['bar']], 'foo=123, bar', ',;'],
            [['foo', '123, bar'], 'foo=123, bar', '='],
            [['foo', '123, bar'], ' foo = 123, bar ', '='],
            [[['foo', '123'], ['bar']], 'foo=123, bar', ',='],
            [[[['foo', '123']], [['bar'], ['foo', '456']]], 'foo=123, bar; foo=456', ',;='],
            [[[['foo', 'a,b;c=d']]], 'foo="a,b;c=d"', ',;='],

            [['foo', 'bar'], 'foo,,,, bar', ','],
            [['foo', 'bar'], ',foo, bar,', ','],
            [['foo', 'bar'], ' , foo, bar, ', ','],
            [['foo bar'], 'foo "bar"', ','],
            [['foo bar'], '"foo" bar', ','],
            [['foo bar'], '"foo" "bar"', ','],

            [[['foo_cookie', 'foo=1&bar=2&baz=3'], ['expires', 'Tue, 22-Sep-2020 06:27:09 GMT'], ['path', '/']], 'foo_cookie=foo=1&bar=2&baz=3; expires=Tue, 22-Sep-2020 06:27:09 GMT; path=/', ';='],
            [[['foo_cookie', 'foo=='], ['expires', 'Tue, 22-Sep-2020 06:27:09 GMT'], ['path', '/']], 'foo_cookie=foo==; expires=Tue, 22-Sep-2020 06:27:09 GMT; path=/', ';='],
            [[['foo_cookie', 'foo=a=b'], ['expires', 'Tue, 22-Sep-2020 06:27:09 GMT'], ['path', '/']], 'foo_cookie=foo="a=b"; expires=Tue, 22-Sep-2020 06:27:09 GMT; path=/', ';='],

            // These are not a valid header values. We test that they parse anyway,
            // and that both the valid and invalid parts are returned.
            [[], '', ','],
            [[], ',,,', ','],
            [['foo', 'bar', 'baz'], 'foo, "bar", "baz', ','],
            [['foo', 'bar, baz'], 'foo, "bar, baz', ','],
            [['foo', 'bar, baz\\'], 'foo, "bar, baz\\', ','],
            [['foo', 'bar, baz\\'], 'foo, "bar, baz\\\\', ','],
        ];
    }

    public function testCombine(): void
    {
        self::assertSame(['foo' => '123'], HeaderUtils::combine([['foo', '123']]));
        self::assertSame(['foo' => true], HeaderUtils::combine([['foo']]));
        self::assertSame(['foo' => true], HeaderUtils::combine([['Foo']]));
        self::assertSame(['foo' => '123', 'bar' => true], HeaderUtils::combine([['foo', '123'], ['bar']]));
    }

    public function testToString(): void
    {
        self::assertSame('foo', HeaderUtils::toString(['foo' => true], ','));
        self::assertSame('foo; bar', HeaderUtils::toString(['foo' => true, 'bar' => true], ';'));
        self::assertSame('foo=123', HeaderUtils::toString(['foo' => '123'], ','));
        self::assertSame('foo="1 2 3"', HeaderUtils::toString(['foo' => '1 2 3'], ','));
        self::assertSame('foo="1 2 3", bar', HeaderUtils::toString(['foo' => '1 2 3', 'bar' => true], ','));
    }

    public function testQuote(): void
    {
        self::assertSame('foo', HeaderUtils::quote('foo'));
        self::assertSame('az09!#$%&\'*.^_`|~-', HeaderUtils::quote('az09!#$%&\'*.^_`|~-'));
        self::assertSame('"foo bar"', HeaderUtils::quote('foo bar'));
        self::assertSame('"foo [bar]"', HeaderUtils::quote('foo [bar]'));
        self::assertSame('"foo \"bar\""', HeaderUtils::quote('foo "bar"'));
        self::assertSame('"foo \\\\ bar"', HeaderUtils::quote('foo \\ bar'));
    }

    public function testUnquote(): void
    {
        self::assertEquals('foo', HeaderUtils::unquote('foo'));
        self::assertEquals('az09!#$%&\'*.^_`|~-', HeaderUtils::unquote('az09!#$%&\'*.^_`|~-'));
        self::assertEquals('foo bar', HeaderUtils::unquote('"foo bar"'));
        self::assertEquals('foo [bar]', HeaderUtils::unquote('"foo [bar]"'));
        self::assertEquals('foo "bar"', HeaderUtils::unquote('"foo \"bar\""'));
        self::assertEquals('foo "bar"', HeaderUtils::unquote('"foo \"\b\a\r\""'));
        self::assertEquals('foo \\ bar', HeaderUtils::unquote('"foo \\\\ bar"'));
    }
}
