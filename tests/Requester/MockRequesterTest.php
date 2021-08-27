<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\MockRequester;
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
        $this->requester->request('GET', '/', []);
    }

    public function testShouldYieldResponsesInOrder(): void
    {
        $this->requester->foresee(
            $r1 = new Response(200, new HeaderBag(), ''),
            $r2 = new Response(200, new HeaderBag(), ''),
            $r3 = new Response(200, new HeaderBag(), ''),
        );

        self::assertSame($r1, $this->requester->request('GET', '/', []));
        self::assertSame($r2, $this->requester->request('GET', '/', []));
        self::assertSame($r3, $this->requester->request('GET', '/', []));
    }
}
