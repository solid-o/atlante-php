<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\MockRequester;
use Solido\Atlante\Requester\Response\LazyResponse;
use Solido\Atlante\Requester\Response\Response;

class MockRequesterTest extends TestCase
{
    private MockRequester $requester;

    protected function setUp(): void
    {
        $this->requester = new MockRequester();
    }

    public function testShouldThrowIfEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->requester->request('GET', '/', [])->getStatusCode();
    }

    public function testShouldCallFilterOnRequest(): void
    {
        $this->requester->foresee(new Response(200, new HeaderBag(), ''));
        $filterExecuted = false;

        $response = $this->requester->request('GET', '/', [], null, function () use (&$filterExecuted) { $filterExecuted = true; });
        self::assertInstanceOf(LazyResponse::class, $response);

        self::assertEquals(200, $response->getStatusCode());
        self::assertTrue($filterExecuted);
    }

    public function testShouldYieldResponsesInOrder(): void
    {
        $this->requester->foresee(
            $r1 = new Response(200, new HeaderBag(), ''),
            $r2 = new Response(200, new HeaderBag(), ''),
            $r3 = new Response(200, new HeaderBag(), ''),
        );

        $getResponse = fn (LazyResponse $r) => (fn () => $this->getResponse())->bindTo($r, LazyResponse::class)();

        self::assertSame($r1, $getResponse($this->requester->request('GET', '/', [])));
        self::assertSame($r2, $getResponse($this->requester->request('GET', '/', [])));
        self::assertSame($r3, $getResponse($this->requester->request('GET', '/', [])));
    }
}
