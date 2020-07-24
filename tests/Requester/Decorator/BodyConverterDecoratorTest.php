<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Request;
use UnexpectedValueException;

use function is_callable;
use function json_encode;
use function Safe\fopen;
use function Safe\stream_get_contents;
use const JSON_THROW_ON_ERROR;

class BodyConverterDecoratorTest extends TestCase
{
    public function testNewIstanceIsReturned(): void
    {
        $decorator = new BodyConverterDecorator();
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
        // @phpstan-ignore-next-line
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, $given));

        $body = $decorated->getBody();
        self::assertEquals($expected, is_callable($body) ? $body() : $body);
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

        yield [null, null];
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDeferredCallable(): void
    {
        $decorator = new BodyConverterDecorator();
        // @phpstan-ignore-next-line
        $decorator->decorate(new Request('GET', '/example.com', null, static fn () => (new RuntimeException())));
    }

    /**
     * @dataProvider provideContents
     *
     * @param string[]|string[][] $givenHeaders
     * @param string[]|string[][] $expectedHeaders
     */
    public function testContentType(?array $givenHeaders, array $expectedHeaders, string $expectedContent): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', $givenHeaders, ['foo' => 'bar', 'bar' => ['bar', 'bar']]));

        $body = $decorated->getBody();
        self::assertEquals($expectedContent, is_callable($body) ? $body() : $body);
    }

    public static function provideContents(): Generator
    {
        yield [['content-type' => 'application/json'], ['content-type' => 'application/json'], '{"foo":"bar","bar":["bar","bar"]}'];
        yield [null, ['content-type' => 'application/json'], '{"foo":"bar","bar":["bar","bar"]}'];
        yield [['x-foo' => 'bar'], ['content-type' => 'application/json', 'x-foo' => 'bar'], '{"foo":"bar","bar":["bar","bar"]}'];
        yield [['content-type' => 'application/x-www-form-urlencoded'], ['content-type' => 'application/x-www-form-urlencoded'], 'foo=bar&bar%5B0%5D=bar&bar%5B1%5D=bar'];
    }

    public function testUnexpectedContentType(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', ['content-type' => 'multipart/form-data'], ['foo' => 'bar']));
        
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to convert Request content body: expected "application/json" or "application/x-www-form-urlencoded" `content-type` header, "multipart/form-data" given');

        $body = $decorated->getBody();
        if (is_callable($body)) {
            $body = $body();
        }
    }
}
