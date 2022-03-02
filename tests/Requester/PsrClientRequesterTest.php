<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\PsrClientRequester;
use Solido\Atlante\Requester\Response\Response as SolidoResponse;
use Solido\Atlante\Requester\Response\ResponseFactoryInterface;

use function Safe\fopen;

class PsrClientRequesterTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|ClientInterface */
    private $client;

    /** @var ObjectProphecy|ResponseFactoryInterface */
    private $responseFactory;
    private PsrClientRequester $requester;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();
        $this->client = $this->prophesize(ClientInterface::class);
        $this->responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $this->requester = new PsrClientRequester($this->client->reveal(), $factory, $factory, $this->responseFactory->reveal());
    }

    public function testUsesBuiltinResponseFactoryByDefault(): void
    {
        $this->client->sendRequest(Argument::any())->willReturn(new Response());
        $requester = new PsrClientRequester($this->client->reveal(), new Psr17Factory(), new Psr17Factory());

        $response = $requester->request('GET', '/', []);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testShouldAcceptStreamsAsBody(): void
    {
        $r = new Response();
        $this->responseFactory->fromResponse($r, null)
            ->shouldBeCalled()
            ->willReturn(new SolidoResponse(200, new HeaderBag(), null));

        $this->client->sendRequest(Argument::that(static function (RequestInterface $request): bool {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals(['accept' => ['application/json']], $request->getHeaders());
            self::assertEquals('foobar', (string) $request->getBody());

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($r);

        $this->requester->request('POST', '/', ['accept' => 'application/json'], fopen('data://text/plain,foobar', 'rb'));
    }

    public function testShouldAcceptStringsAsBody(): void
    {
        $r = new Response();
        $this->responseFactory->fromResponse($r, null)
            ->shouldBeCalled()
            ->willReturn(new SolidoResponse(200, new HeaderBag(), null));

        $this->client->sendRequest(Argument::that(static function (RequestInterface $request): bool {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals(['accept' => ['application/json']], $request->getHeaders());
            self::assertEquals('foobar', (string) $request->getBody());

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($r);

        $this->requester->request('POST', '/', ['accept' => 'application/json'], 'foobar');
    }

    public function testShouldAcceptCallablesAsBody(): void
    {
        $r = new Response();
        $this->responseFactory->fromResponse($r, null)
            ->shouldBeCalled()
            ->willReturn(new SolidoResponse(200, new HeaderBag(), null));

        $this->client->sendRequest(Argument::that(static function (RequestInterface $request): bool {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals(['accept' => ['application/json']], $request->getHeaders());
            self::assertEquals('foobar', (string) $request->getBody());

            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($r);

        $this->requester->request('POST', '/', ['accept' => 'application/json'], static fn () => 'foobar');
    }

    public function testShouldThrowIfCallableBodyHasInvalidReturnType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Request body should be a string or a stream resource, "stdClass" passed');

        $this->requester->request('POST', '/', ['accept' => 'application/json'], static fn () => (object) []);
    }
}
