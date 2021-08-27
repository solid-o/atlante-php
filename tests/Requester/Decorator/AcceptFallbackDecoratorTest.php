<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Generator;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Decorator\AcceptFallbackDecorator;
use Solido\Atlante\Requester\Request;

class AcceptFallbackDecoratorTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $given
     * @param array<string,mixed> $expected
     *
     * @dataProvider provideDecorationCases
     */
    public function testDecoration(?string $fallback, ?array $given, array $expected): void
    {
        $decorator = new AcceptFallbackDecorator($fallback);
        $decorated = $decorator->decorate(new Request('GET', '/example.com', $given, ''));

        $headers = $decorated->getHeaders();

        self::assertEquals($expected, $headers);
    }

    public static function provideDecorationCases(): Generator
    {
        yield [null, [], ['accept' => ['application/json']]];
        yield [null, null, ['accept' => ['application/json']]];
        yield [null, ['foo' => ['bar']], ['accept' => ['application/json'], 'foo' => ['bar']]];
        yield [null, ['accept' => ['text/html']], ['accept' => ['text/html']]];

        yield ['application/xml', [], ['accept' => ['application/xml']]];
        yield ['application/xml', ['accept' => ['text/html']], ['accept' => ['text/html']]];
    }
}
