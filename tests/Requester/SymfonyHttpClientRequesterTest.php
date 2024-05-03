<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\Response as SolidoResponse;
use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\SymfonyHttpClientRequester;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function fopen;

class SymfonyHttpClientRequesterTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|HttpClientInterface */
    private $client;

    /** @var ObjectProphecy|ResponseFactoryInterface */
    private $responseFactory;
    private SymfonyHttpClientRequester $requester;

    protected function setUp(): void
    {
        $this->client = $this->prophesize(HttpClientInterface::class);
        $this->responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $this->requester = new SymfonyHttpClientRequester($this->client->reveal(), $this->responseFactory->reveal());
    }

    public function testUsesBuiltinResponseFactoryByDefault(): void
    {
        $this->client->request(Argument::cetera())->will(function ($args) { // phpcs:ignore
            return MockResponse::fromRequest('POST', 'https://localhost', $args[2], new MockResponse());
        });
        $requester = new SymfonyHttpClientRequester($this->client->reveal());

        $response = $requester->request('GET', '/', []);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testShouldAcceptStreamsAsBody(): void
    {
        $r = new MockResponse();
        $this->responseFactory->fromResponse($r, null)
            ->shouldBeCalled()
            ->willReturn(new SolidoResponse(200, new HeaderBag(), null));

        $stream = fopen('data://text/plain,foobar', 'rb');
        $this->client->request('POST', '/', [
            'headers' => ['accept' => 'application/json'],
            'body' => $stream,
            'max_redirects' => 0,
        ])
            ->shouldBeCalled()
            ->willReturn($r);

        $this->requester->request('POST', '/', ['accept' => 'application/json'], $stream);
    }
}
