<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response;

use PHPUnit\Framework\TestCase;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;

class BadResponseTest extends TestCase
{
    public function testCanCreate(): void
    {
        $response = new BadResponse(new HeaderBag(['Content-Type' => 'application/json']), [
            'name' => 'foo',
            'errors' => [],
            'children' => [],
        ]);

        self::assertSame(400, $response->getStatusCode());

        $errors = $response->getErrors();
        self::assertInstanceOf(BadResponsePropertyTree::class, $errors);
    }
}
