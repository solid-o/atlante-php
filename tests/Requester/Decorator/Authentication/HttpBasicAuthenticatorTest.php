<?php

declare(strict_types=1);

namespace Tests\Requester\Decorator\Authentication;

use Generator;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Decorator\Authentication\HttpBasicAuthenticator;
use Solido\Atlante\Requester\Request;
use function is_array;
use function reset;

class HttpBasicAuthenticatorTest extends TestCase
{
    /**
     * @dataProvider provideDecorationCases
     */
    public function testDecoration(string $usernameOrEncodedAuth, ?string $password, ?string $givenAuthHeader, ?string $expectedAuthHeader): void
    {
        $decorator = new HttpBasicAuthenticator($usernameOrEncodedAuth, $password);
        $request = new Request('GET', '/example.com', $givenAuthHeader === null ? null : ['authorization' => $givenAuthHeader], 'foo');

        $decorated = $decorator->decorate($request);
        $headers = $decorated->getHeaders();

        $auth = $headers['authorization'] ?? null;
        if (is_array($auth)) {
            $auth = reset($auth);
        }

        self::assertEquals($expectedAuthHeader, $auth);
        self::assertSame('GET', $decorated->getMethod());
        self::assertSame('/example.com', $decorated->getUrl());
        self::assertSame('foo', $decorated->getBody());
    }

    public static function provideDecorationCases(): Generator
    {
        yield ['1295AC56-C999-41C4-8384-B8D8D2D2F3AA', 'secret', null, 'Basic MTI5NUFDNTYtQzk5OS00MUM0LTgzODQtQjhEOEQyRDJGM0FBOnNlY3JldA=='];
        yield ['1295AC56-C999-41C4-8384-B8D8D2D2F3AA', '', null, 'Basic MTI5NUFDNTYtQzk5OS00MUM0LTgzODQtQjhEOEQyRDJGM0FB'];
        yield ['MTI5NUFDNTYtQzk5OS00MUM0LTgzODQtQjhEOEQyRDJGM0FBOnNlY3JldA==', null, null, 'Basic MTI5NUFDNTYtQzk5OS00MUM0LTgzODQtQjhEOEQyRDJGM0FBOnNlY3JldA=='];

        yield ['1295AC56-C999-41C4-8384-B8D8D2D2F3AA', 'secret', 'fooAuth', 'fooAuth'];
        yield ['1295AC56-C999-41C4-8384-B8D8D2D2F3AA', '', 'fooAuth', 'fooAuth'];
        yield ['MTI5NUFDNTYtQzk5OS00MUM0LTgzODQtQjhEOEQyRDJGM0FBOnNlY3JldA==', null, 'fooAuth', 'fooAuth'];
    }

    /** @dataProvider provideHeaderDecorationCases */
    public function testHeaderDecorationPreservePresets(?HeaderBag $given, HeaderBag $expected): void
    {
        $decorator = new HttpBasicAuthenticator('fooAuth');
        $request = new Request('GET', '/example.com', $given === null ? $given : $given->all(), 'foo');

        $decorated = $decorator->decorate($request);
        self::assertEquals($expected->all(), $decorated->getHeaders());
    }

    public static function provideHeaderDecorationCases(): Generator
    {
        yield [new HeaderBag(['foo' => 'bar']), new HeaderBag(['foo' => 'bar', 'authorization' => 'Basic fooAuth'])];
        yield [null, new HeaderBag(['authorization' => 'Basic fooAuth'])];
    }
}
