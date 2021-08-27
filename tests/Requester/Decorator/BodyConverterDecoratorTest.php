<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Closure;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Request;
use UnexpectedValueException;

use function curl_init;
use function is_callable;
use function json_encode;
use function Safe\fopen;

use const JSON_THROW_ON_ERROR;

class BodyConverterDecoratorTest extends TestCase
{
    public function testNewInstanceIsReturned(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate($request = new Request('GET', '/foo', null, 'foo'));
        self::assertEquals($request, $decorated);
        self::assertNotSame($request, $decorated);
    }

    /**
     * @param array|string|resource|Closure|iterable<string>|null $given
     * @param string|resource|null $expected
     * @phpstan-param array|string|resource|Closure(): string|iterable<string>|null $given
     *
     * @dataProvider provideDecorateCases
     */
    public function testDecorateBody($given, $expected): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, $given));

        $body = $decorated->getBody();
        if (is_callable($body)) {
            self::assertEquals($expected, $body());
            self::assertEquals('', $body());
        } else {
            self::assertEquals($expected, $body);
        }
    }

    public static function provideDecorateCases(): Generator
    {
        yield ['foobar', 'foobar'];
        yield [$a = ['foo' => 'bar', 'bar' => ['foo', 'foobar']], json_encode($a, JSON_THROW_ON_ERROR)];
        yield [static fn (): string => 'foobar', 'foobar'];
        yield [$resource = fopen('data://text/plain,foobar', 'rb'), $resource];
        yield [$a = ['foo' => true, 'bar' => ['foo', 1, .12, false]], json_encode($a, JSON_THROW_ON_ERROR)];

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

    public function testShouldReadStreamChunked(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, fopen('data://text/plain,foobar', 'rb')));

        $body = $decorated->getBody();
        self::assertEquals('foo', $body(3));
        self::assertEquals('bar', $body(3));
        self::assertEquals('', $body(3));
    }

    public function testShouldReadStringChunked(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, static fn () => 'foobar'));

        $body = $decorated->getBody();
        self::assertEquals('foo', $body(3));
        self::assertEquals('bar', $body(3));
        self::assertEquals('', $body(3));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDeferredCallable(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorator->decorate(new Request('GET', '/example.com', null, static function (): void {
            throw new RuntimeException('This exception should not be triggered');
        }));
    }

    /**
     * @param string[]|string[][] $givenHeaders
     *
     * @dataProvider provideContents
     */
    public function testContentType(?array $givenHeaders, string $expectedContent): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', $givenHeaders, ['foo' => 'bar', 'bar' => ['bar', 'bar']]));

        $body = $decorated->getBody();
        self::assertEquals($expectedContent, is_callable($body) ? $body() : $body);
    }

    public static function provideContents(): Generator
    {
        yield [['content-type' => 'application/json'], '{"foo":"bar","bar":["bar","bar"]}'];
        yield [null, '{"foo":"bar","bar":["bar","bar"]}'];
        yield [['x-foo' => 'bar'], '{"foo":"bar","bar":["bar","bar"]}'];
        yield [['content-type' => 'application/x-www-form-urlencoded'], 'foo=bar&bar%5B0%5D=bar&bar%5B1%5D=bar'];
    }

    public function testUnexpectedContentType(): void
    {
        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', ['content-type' => 'multipart/form-data'], ['foo' => 'bar']));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to convert Request content body: expected "application/json" or "application/x-www-form-urlencoded" `content-type` header, "multipart/form-data" given');

        $body = $decorated->getBody();
        if (! is_callable($body)) {
            return;
        }

        $body();
    }

    public function testDecorateBodyWithNonStreamResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument #0 passed to Solido\Atlante\Requester\Decorator\BodyConverterDecorator::prepare has to be null, string, stream resource, iterable or callable');

        $decorator = new BodyConverterDecorator();
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, curl_init('https://localhost')));

        $body = $decorated->getBody();
        $body();
    }
}
