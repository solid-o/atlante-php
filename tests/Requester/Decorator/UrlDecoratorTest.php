<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Generator;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Decorator\UrlDecorator;
use Solido\Atlante\Requester\Request;

class UrlDecoratorTest extends TestCase
{
    public function testNewIstanceIsReturned(): void
    {
        $decorator = new UrlDecorator('/');
        $decorated = $decorator->decorate($request = new Request('GET', '/foo'));
        self::assertEquals($request, $decorated);
        self::assertNotSame($request, $decorated);
    }

    /**
     * @dataProvider provideDecorateCases
     */
    public function testDecorate(string $baseUrl, string $url, string $expected): void
    {
        $decorator = new UrlDecorator($baseUrl);
        $decorated = $decorator->decorate(new Request('GET', $url));
        self::assertEquals($expected, $decorated->url);
    }

    public static function provideDecorateCases(): Generator
    {
        yield ['http://api.example.test', '/foo', 'http://api.example.test/foo'];
        yield ['https://api.example.com', '/foo', 'https://api.example.com/foo'];

        yield ['http://api.example.test/', 'http://localhost/foo/', 'http://localhost/foo/'];
        yield ['https://api.example.com/foo/', 'bar', 'https://api.example.com/foo/bar'];
        yield ['https://api.example.com/foo', 'bar', 'https://api.example.com/bar'];
        yield ['https://api.example.com/foo', '/bar', 'https://api.example.com/bar'];
        yield ['https://api.example.com', 'foo', 'https://api.example.com/foo'];

        yield ['http://api.example.test/foo?test=1', '/foo', 'http://api.example.test/foo'];
        yield ['http://api.example.test/foo?test=1', '/foo?test=2&c=a', 'http://api.example.test/foo?test=2&c=a'];

        yield ['http://api.example.test/foo/#frag', 'bar', 'http://api.example.test/foo/bar'];
        yield ['http://api.example.test/foo/', 'bar#frag', 'http://api.example.test/foo/bar#frag'];
        yield ['http://api.example.test/foo/#no-frag', 'bar#frag', 'http://api.example.test/foo/bar#frag'];
        yield ['http://api.example.test/foo/#no-frag', '/bar#frag', 'http://api.example.test/bar#frag'];
    }
}
