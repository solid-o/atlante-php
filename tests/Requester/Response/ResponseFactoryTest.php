<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response;

use Generator;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\AccessDeniedResponse;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\LazyResponse;
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
    use ProphecyTrait;

    /**
     * @phpstan-param class-string $responseClassname
     * @phpstan-param array<string, string[]> $expectedHeaders
     */
    #[DataProvider('provideParseCases')]
    public function testFromResponse($requesterResponse, ResponseFactoryInterface $factory, string $responseClassname, int $statusCode, $expectedData, array $expectedHeaders): void
    {
        $response = $factory->fromResponse($requesterResponse);

        self::assertInstanceOf(LazyResponse::class, $response);
        $response = (fn () => $this->getResponse())->bindTo($response, LazyResponse::class)();

        self::assertInstanceOf($responseClassname, $response);
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertEquals($expectedData, $response->getData());
        self::assertEquals($expectedHeaders, $response->getHeaders()->all());
    }

    public static function provideParseCases(): Generator
    {
        foreach (['SfResponse', 'PsrResponse'] as $kind) {
            $mockMethod = 'mock' . $kind;
            $factory = $kind === 'SfResponse' ? new SymfonyHttpClientResponseFactory() : new ResponseFactory();

            $headers = [];
            $statusCode = 200;
            $content = 'foobar';

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content, []];

            $content = '{"_id":"foo"}';

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content, []];

            $headers = ['content-type' => 'application/json'];

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 201;

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 300;

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 403;

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, AccessDeniedResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 404;

            yield [self::$mockMethod($statusCode, $content, $headers), $factory, NotFoundResponse::class, $statusCode, (object) ['_id' => 'foo'], ['content-type' => ['application/json']]];

            $statusCode = 400;
            $content = '{"name":"foo","errors":["Required."],"children":[]}';

            yield [
                self::$mockMethod($statusCode, $content, $headers),
                $factory,
                BadResponse::class,
                $statusCode,
                new BadResponsePropertyTree('foo', ['Required.'], []),
                ['content-type' => ['application/json']],
            ];

            $statusCode = 500;

            yield [
                self::$mockMethod($statusCode, $content, $headers),
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
            protected static function decodeData(HeaderBag $headers, string $data): mixed
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
    private static function mockSfResponse(int $statusCode, string $content, array $headers): SymfonyHttpClientResponse
    {
        return new readonly class($statusCode, $headers, $content) implements SymfonyHttpClientResponse {
            public function __construct(private int $statusCode, private array $headers, private string $content)
            {
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function getHeaders(bool $throw = true): array
            {
                return $this->headers;
            }

            public function getContent(bool $throw = true): string
            {
                return $this->content;
            }

            public function toArray(bool $throw = true): array
            {
                // TODO: Implement toArray() method.
            }

            public function cancel(): void
            {
                // TODO: Implement cancel() method.
            }

            public function getInfo(?string $type = null): mixed
            {
                // TODO: Implement getInfo() method.
            }
        };
    }

    /**
     * @param string[]|string[][] $headers
     */
    // @phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private static function mockPsrResponse(int $statusCode, string $content, array $headers): PsrResponseInterface
    {
        return new readonly class($statusCode, $headers, $content) implements PsrResponseInterface {
            public function __construct(private int $statusCode, private array $headers, private string $content)
            {
            }

            public function getProtocolVersion()
            {
                // TODO: Implement getProtocolVersion() method.
            }

            public function withProtocolVersion(string $version)
            {
                // TODO: Implement withProtocolVersion() method.
            }

            public function getHeaders()
            {
                return $this->headers;
            }

            public function hasHeader(string $name)
            {
                // TODO: Implement hasHeader() method.
            }

            public function getHeader(string $name)
            {
                // TODO: Implement getHeader() method.
            }

            public function getHeaderLine(string $name)
            {
                // TODO: Implement getHeaderLine() method.
            }

            public function withHeader(string $name, $value)
            {
                // TODO: Implement withHeader() method.
            }

            public function withAddedHeader(string $name, $value)
            {
                // TODO: Implement withAddedHeader() method.
            }

            public function withoutHeader(string $name)
            {
                // TODO: Implement withoutHeader() method.
            }

            public function getBody()
            {
                return $this->content;
            }

            public function withBody(StreamInterface $body)
            {
                // TODO: Implement withBody() method.
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus(int $code, string $reasonPhrase = ''): PsrResponseInterface
            {
                // TODO: Implement withStatus() method.
            }

            public function getReasonPhrase(): string
            {
                // TODO: Implement getReasonPhrase() method.
            }
        };
    }
}
