<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator;

use Generator;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Decorator\VersionSetterDecorator;
use Solido\Atlante\Requester\Request;

class VersionSetterDecoratorTest extends TestCase
{
    public function testNewIstanceIsReturned(): void
    {
        $decorator = new VersionSetterDecorator('foo');
        $decorated = $decorator->decorate($request = new Request('GET', '/foo', null, 'foo'));
        self::assertNotSame($request, $decorated);
    }

    /**
     * @param array<string,string>      $expectedHeaders
     * @param array<string,string>|null $givenHeaders
     *
     * @dataProvider provideDecorateCases
     */
    public function testDecorate(string $version, array $expectedHeaders, ?array $givenHeaders): void
    {
        $decorator = new VersionSetterDecorator($version);
        $decorated = $decorator->decorate(new Request('GET', '/example.com', null, $givenHeaders));
        self::assertEquals($expectedHeaders, $decorated->getHeaders());
    }

    public static function provideDecorateCases(): Generator
    {
        yield ['foo', ['version' => ['foo']], []];
        yield ['foo', ['version' => ['foo']], null];
        yield ['bar', ['version' => ['bar']], ['version' => 'foo']];
        yield ['foo', ['version' => ['foo']], ['version' => ['bar', 'bar']]];
    }
}
