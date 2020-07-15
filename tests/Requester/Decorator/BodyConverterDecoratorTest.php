<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Request;
use function fopen;
use function is_callable;
use function json_encode;
use function Safe\stream_get_contents;
use const JSON_THROW_ON_ERROR;

class BodyConverterDecoratorTest extends TestCase
{
    public function testNewIstanceIsReturned(): void
    {
        $decorator = new BodyConverterDecorator('/');
        $decorated = $decorator->decorate($request = new Request('GET', '/foo', null, 'foo'));
        self::assertEquals($request, $decorated);
        self::assertNotSame($request, $decorated);
    }

    /**
     * @param array|string|resource|iterable<string|resource>|Closure $given
     *
     * @dataProvider provideDecorateCases
     */
    public function testDecorate($given, ?string $expected): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, $given));

        self::assertEquals($expected, is_callable($body = $decorated->getBody()) ? $body() : $body);
    }

    public static function provideDecorateCases(): Generator
    {
        yield ['foobar', 'foobar'];
        yield [$a = ['foo' => 'bar', 'bar' => ['foo', 'foobar']], json_encode($a, JSON_THROW_ON_ERROR)];
        yield [static fn (): string => 'foobar', 'foobar'];
        yield [stream_get_contents(fopen('data://text/plain,foobar', 'rb')), 'foobar'];

        yield [
            // @phpcs:ignore Squiz.Arrays.ArrayDeclaration.ValueNoNewline
            static function (): Generator {
                yield 'foo';
                yield 'bar';
            },
            '["foo","bar"]',
        ];

        yield [null, 'null'];
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDeferredCallable(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorator->decorate(new Request('GET', '/example.com', null, static fn () => (new RuntimeException())));
    }
}
