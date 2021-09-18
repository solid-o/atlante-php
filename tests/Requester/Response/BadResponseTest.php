<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Atlante\Requester\Response\Parser\BadResponse\BadResponsePropertyTreeParserFactory;
use Solido\Atlante\Requester\Response\Parser\BadResponse\BadResponsePropertyTreeParserInterface;

class BadResponseTest extends TestCase
{
    use ProphecyTrait;

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

    public function testWillUseTheGivenPropertyTreeFactory(): void
    {
        $factory = $this->prophesize(BadResponsePropertyTreeParserFactory::class);
        $parser = $this->prophesize(BadResponsePropertyTreeParserInterface::class);
        $factory->factory([])->willReturn($parser);
        $parser->parse([])->willReturn(new BadResponsePropertyTree('', [], []));

        $response = new BadResponse(new HeaderBag(), [], $factory->reveal());

        self::assertSame(400, $response->getStatusCode());

        $errors = $response->getErrors();
        self::assertInstanceOf(BadResponsePropertyTree::class, $errors);
    }
}
