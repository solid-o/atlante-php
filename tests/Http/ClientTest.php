<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http;

use ArrayObject;
use Closure;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\Atlante\Http\Client;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Exception\AbstractException;
use Solido\Atlante\Requester\Exception\AccessDeniedException;
use Solido\Atlante\Requester\Exception\BadRequestException;
use Solido\Atlante\Requester\Exception\InvalidRequestException;
use Solido\Atlante\Requester\Exception\NotFoundException;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\AbstractResponse;
use Solido\Atlante\Requester\Response\AccessDeniedResponse;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\NotFoundResponse;
use Solido\Atlante\Requester\Response\Response;
use TypeError;

use function assert;
use function fopen;

class ClientTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|DecoratorInterface */
    private $decorator1;

    /** @var ObjectProphecy|DecoratorInterface */
    private $decorator2;

    /** @var ObjectProphecy|RequesterInterface */
    private $requester;
    private Client $client;

    protected function setUp(): void
    {
        $this->decorator1 = $this->prophesize(DecoratorInterface::class);
        $this->decorator2 = $this->prophesize(DecoratorInterface::class);

        $this->decorator1->decorate(Argument::any())->willReturnArgument(0);
        $this->decorator2->decorate(Argument::any())->willReturnArgument(0);

        $this->requester = $this->prophesize(RequesterInterface::class);
        $this->client = new Client($this->requester->reveal(), [$this->decorator1->reveal(), $this->decorator2->reveal()]);
    }

    public function testDeleteRequest(): void
    {
        $this->requester->request('DELETE', '/', ['accept' => ['application/json']], null, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->delete('/', []);
    }

    public function testGetRequest(): void
    {
        $this->requester->request('GET', '/', ['accept' => ['application/json']], null, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->get('/', []);
    }

    public function testPostRequest(): void
    {
        $this->requester->request('POST', '/', ['accept' => ['application/json']], '{}', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', '{}', []);
    }

    public function testPutRequest(): void
    {
        $this->requester->request('PUT', '/', ['accept' => ['application/json']], '{}', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->put('/', '{}', []);
    }

    public function testPatchRequest(): void
    {
        $this->requester->request('PATCH', '/', ['accept' => ['application/json']], '{}', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->patch('/', '{}', []);
    }

    /**
     * @dataProvider provideNoBodyMethods
     */
    public function testRequestShouldClearRequestDataForDisallowedMethods(string $method): void
    {
        $this->requester->request($method, '/', ['accept' => ['application/json']], null, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->request($method, '/', 'test', []);
    }

    public function provideNoBodyMethods(): iterable
    {
        yield ['GET'];
        yield ['HEAD'];
        yield ['DELETE'];
    }

    public function testRequestShouldPassAllTheHeaders(): void
    {
        $this->requester->request('POST', '/', [
            'x-powered-by' => ['PHPUNIT'],
            'accept' => ['application/json'],
        ], '{}', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->request('POST', '/', '{}', ['X-Powered-By' => 'PHPUNIT']);
    }

    public function testShouldForwardRequestToTheDecorators(): void
    {
        $this->decorator1->decorate(Argument::that(static function (Request $request): bool {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals('/', $request->getUrl());

            return true;
        }))
            ->shouldBeCalled()
            ->will(function ($args) { // phpcs:ignore
                $request = $args[0];
                assert($request instanceof Request);
                $headers = $request->getHeaders();
                $headers['x-test'] = ['great work!'];

                return new Request('POST', 'https://example.org/', $headers, $request->getBody());
            });

        $this->decorator2->decorate(Argument::that(static function (Request $request): bool {
            self::assertEquals('POST', $request->getMethod());
            self::assertEquals('https://example.org/', $request->getUrl());

            return true;
        }))
            ->shouldBeCalled()
            ->will(function ($args) { // phpcs:ignore
                $request = $args[0];
                assert($request instanceof Request);

                return new Request('PUT', 'https://example.com/second/', $request->getHeaders(), 'decorated-body');
            });

        $this->requester->request('PUT', 'https://example.com/second/', [
            'x-powered-by' => ['PHPUNIT'],
            'x-test' => ['great work!'],
            'accept' => ['application/json'],
        ], 'decorated-body', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->request('POST', '/', '{}', ['X-Powered-By' => 'PHPUNIT']);
    }

    /**
     * @dataProvider provideInvalidResponses
     */
    public function testShouldFilterResponseAndThrowIfInvalid(string $exceptionClass, AbstractResponse $response): void
    {
        $this->expectException($exceptionClass);
        $this->requester->request('GET', '/', ['accept' => ['application/json']], null, Argument::type('callable'))
            ->shouldBeCalled()
            ->will(function ($args) use ($response) {
                ($args[4])($response);

                return $response;
            });

        try {
            $this->client->get('/', []);
        } catch (AbstractException $e) {
            self::assertSame($response, $e->getResponse());

            throw $e;
        }
    }

    public function provideInvalidResponses(): iterable
    {
        yield [
            BadRequestException::class,
            new BadResponse(new HeaderBag(), (object) [
                'errors' => [],
                'name' => '',
            ]),
        ];

        yield [AccessDeniedException::class, new AccessDeniedResponse(new HeaderBag(), '{}')];
        yield [NotFoundException::class, new NotFoundResponse(new HeaderBag(), '{}')];
        yield [InvalidRequestException::class, new InvalidResponse(500, new HeaderBag(), '{}')];
    }

    public function testShouldNotNormalizeStreamBody(): void
    {
        $stream = fopen('php://temp', 'rb+');
        $this->requester->request('POST', '/', ['accept' => ['application/json']], $stream, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', $stream, []);
    }

    /**
     * @dataProvider provideIterableBody
     */
    public function testShouldNormalizeIterableBody(iterable $iterable): void
    {
        $this->requester->request('POST', '/', [
            'content-type' => ['application/json'],
            'accept' => ['application/json'],
        ], Argument::that(static function (Closure $closure): bool {
            self::assertEquals('{"test":"great"}', $closure());

            return true;
        }), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', $iterable, []);
    }

    public function provideIterableBody(): iterable
    {
        yield [['test' => 'great']];
        yield [new ArrayObject(['test' => 'great'])];
        yield [
            (static function () {
                yield 'test' => 'great';
            })(),
        ];
    }

    public function testShouldThrowTryingToNormalizeAnObject(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Given request body has to be a string, a stream resource, a function that returns a string, a generator yielding strings or an iterable of strings, "' . self::class . '" given');
        $this->client->post('/', $this, []);
    }

    public function testShouldNormalizeCallablesNotReturningString(): void
    {
        $this->requester->request('POST', '/', [
            'content-type' => ['application/json'],
            'accept' => ['application/json'],
        ], Argument::that(static function (Closure $closure): bool {
            self::assertEquals('{"test":"great"}', $closure());

            return true;
        }), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', static function () {
            yield 'test' => 'great';
        }, []);
    }

    public function testShouldNormalizeCallablesReturningString(): void
    {
        $this->requester->request('POST', '/', [
            'accept' => ['application/json'],
        ], Argument::that(static function (Closure $closure): bool {
            self::assertEquals('{"test":"great"}', $closure());

            return true;
        }), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', static function (): string {
            return '{"test":"great"}';
        }, []);
    }

    public function testShouldNormalizeCallablesReturningIterable(): void
    {
        $this->requester->request('POST', '/', [
            'content-type' => ['application/json'],
            'accept' => ['application/json'],
        ], Argument::that(static function (Closure $closure): bool {
            self::assertEquals('{"test":"great"}', $closure());

            return true;
        }), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', static function (): array {
            return ['test' => 'great'];
        }, []);
    }

    public function testShouldNormalizeCallableNotClosures(): void
    {
        $this->requester->request('POST', '/', [
            'accept' => ['application/json'],
        ], Argument::that(static function (Closure $closure): bool {
            self::assertEquals('{"test":"great"}', $closure());

            return true;
        }), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), []));

        $this->client->post('/', [$this, 'helperMethod'], []);
    }

    public function helperMethod(): string
    {
        return '{"test":"great"}';
    }
}
