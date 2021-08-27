<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Request;

class RequestTest extends TestCase
{
    public function testPropertyAccess(): void
    {
        $request = new Request('POST', 'https://localhost', ['accept' => 'application/json'], '{}');

        self::assertEquals('POST', $request->method);
        self::assertEquals('https://localhost', $request->url);
        self::assertEquals(['accept' => ['application/json']], $request->headers);
        self::assertEquals('{}', $request->body);
    }

    public function testInvalidPropertyAccess(): void
    {
        $request = new Request('POST', 'https://localhost', ['accept' => 'application/json'], '{}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined property: Solido\Atlante\Requester\Request::invalid_property');

        $x = $request->invalid_property;
    }
}
