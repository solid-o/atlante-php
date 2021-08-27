<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Exception;

use PHPUnit\Framework\TestCase;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Exception\AbstractException;
use Solido\Atlante\Requester\Response\Response;
use stdClass;

class AbstractExceptionTest extends TestCase
{
    public function testExceptionMessageIsNotDiscarded(): void
    {
        $ex = new ConcreteException(new Response(200, new HeaderBag(), new stdClass()), 'Exception message');
        self::assertEquals('Exception message', $ex->getMessage());
    }

    public function testExceptionCodeIsAlways0(): void
    {
        $ex = new ConcreteException(new Response(200, new HeaderBag(), new stdClass()));
        self::assertEquals(0, $ex->getCode());
    }

    public function testExceptionShouldExposeStatusCode(): void
    {
        $ex = new ConcreteException(new Response(200, new HeaderBag(), new stdClass()));
        self::assertEquals(200, $ex->getStatusCode());
        $ex = new ConcreteException(new Response(500, new HeaderBag(), new stdClass()));
        self::assertEquals(500, $ex->getStatusCode());
    }

    public function testExceptionShouldExposeResponseObjet(): void
    {
        $ex = new ConcreteException($response = new Response(200, new HeaderBag(), new stdClass()));
        self::assertSame($response, $ex->getResponse());
    }
}

class ConcreteException extends AbstractException
{
}
