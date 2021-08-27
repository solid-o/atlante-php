<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response;

use Generator;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\AccessDeniedResponse;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\NotFoundResponse;
use Solido\Atlante\Requester\Response\Response;
use Solido\Atlante\Requester\Response\ResponseFactory;
use Solido\Atlante\Requester\Response\ResponseFactoryInterface;
use Solido\Atlante\Requester\Response\SymfonyHttpClientResponseFactory;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyHttpClientResponse;
use TypeError;

use function assert;

class ResponseFactoryTest extends TestCase
{
    /**
     * @phpstan-param class-string $responseClassname
     * @phpstan-param array<string, string[]> $expectedHeaders
     *
     * @dataProvider provideParseCases
     */
    public function testFromResponse($requesterResponse, ResponseFactoryInterface $factory, string $responseClassname, int $statusCode, $expectedData, array $expectedHeaders): void
    {
        $response = $factory->fromResponse($requesterResponse);

        self::assertInstanceOf($responseClassname, $response);
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertEquals($expectedData, $response->getData());
        self::assertEquals($expectedHeaders, $response->getHeaders()->all());
    }

    public function provideParseCases(): Generator
    {
        foreach (['SfResponse', 'PsrResponse'] as $kind) {
            $mockMethod = 'mock' . $kind;
            $factory = $kind === 'SfResponse' ? new SymfonyHttpClientResponseFactory() : new ResponseFactory();

            $headers = [];
            $statusCode = 200;
            $content = 'foobar';

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content, []];

            $content = '{"_id":"foo"}';

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content, []];

            $headers = ['content-type' => 'application/json'];

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 201;

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 300;

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 403;

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, AccessDeniedResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 404;

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, NotFoundResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 400;
            $content = '{"name":"foo","errors":["Required."],"children":[]}';

            yield [
                $this->$mockMethod($statusCode, $content, $headers),
                $factory,
                BadResponse::class,
                $statusCode,
                BadResponsePropertyTree::parse([
                    'name' => 'foo',
                    'errors' => ['Required.'],
                    'children' => [],
                ]),
                ['content-type' => ['application/json']],
            ];

            $statusCode = 500;

            yield [
                $this->$mockMethod($statusCode, $content, $headers),
                $factory,
                InvalidResponse::class,
                $statusCode,
                ((object) [
                    'name' => 'foo',
                    'errors' => ['Required.'],
                    'children' => [],
                ]),
                ['content-type' => ['application/json']],
            ];
        }
    }

    public function testUnexpectedResponse(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument 1 passed to Solido\Atlante\Requester\Response\ResponseFactory::fromResponse must be an instance of Psr\Http\Message\ResponseInterface, stdClass passed');

        $factory = new ResponseFactory();
        $factory->fromResponse((object) []);
    }

    public function testUnexpectedResponseClass(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument 1 passed to Solido\Atlante\Requester\Response\SymfonyHttpClientResponseFactory::fromResponse must be an instance of Symfony\Contracts\HttpClient\ResponseInterface, Nyholm\Psr7\Response passed.');

        $factory = new SymfonyHttpClientResponseFactory();
        $factory->fromResponse(new PsrResponse());
    }

    public function testDecodeDataOverridable(): void
    {
        $factory = new class extends ResponseFactory {
            protected static function decodeData(HeaderBag $headers, string $data)
            {
                return (object) ['test'];
            }
        };

        $response = $factory->fromResponse(new PsrResponse());
        self::assertEquals((object) ['test'], $response->getData());
    }

    /**
     * @param string[]|string[][] $headers
     */
    // @phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function mockSfResponse(int $statusCode, string $content, array $headers): SymfonyHttpClientResponse
    {
        $response = $this->prophesize(SymfonyHttpClientResponse::class);

        $response->getStatusCode()->willReturn($statusCode);
        $response->getHeaders(false)->willReturn($headers);
        $response->getContent(false)->willReturn($content);

        $r = $response->reveal();
        assert($r instanceof SymfonyHttpClientResponse);

        return $r;
    }

    /**
     * @param string[]|string[][] $headers
     */
    // @phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function mockPsrResponse(int $statusCode, string $content, array $headers): PsrResponseInterface
    {
        $response = $this->prophesize(PsrResponseInterface::class);

        $response->getStatusCode()->willReturn($statusCode);
        $response->getHeaders()->willReturn($headers);
        $response->getBody()->willReturn($content);

        $r = $response->reveal();
        assert($r instanceof PsrResponseInterface);

        return $r;
    }
}
