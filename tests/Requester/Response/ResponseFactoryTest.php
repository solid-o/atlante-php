<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response;

use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Atlante\Requester\Response\InvalidResponse;
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
     * @param SymfonyHttpClientResponse $requesterResponse
     * @param object|string $expectedData
     *
     * @phpstan-param class-string $responseClassname
     * @dataProvider provideParseCases
     */
    public function testFromResponse($requesterResponse, ResponseFactoryInterface $factory, string $responseClassname, int $statusCode, $expectedData): void
    {
        $response = $factory->fromResponse($requesterResponse);
        self::assertInstanceOf($responseClassname, $response);
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertEquals($expectedData, $response->getData());
    }

    public function provideParseCases(): Generator
    {
        foreach (['SfResponse', 'PsrResponse'] as $kind) {
            $mockMethod = 'mock' . $kind;
            $factory = 'SfResponse' === $kind ? new SymfonyHttpClientResponseFactory() : new ResponseFactory();

            $headers = [];
            $statusCode = 200;
            $content = 'foobar';

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content];

            $content = '{"_id":"foo"}';

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, InvalidResponse::class, $statusCode, $content];

            $headers = ['content-type' => 'application/json'];

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo']];

            $statusCode = 201;
            $headers = ['content-type' => 'application/json'];

            yield [$this->$mockMethod($statusCode, $content, $headers), $factory, Response::class, $statusCode, (object) ['_id' => 'foo']];

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
            ];
        }
    }

    public function testUnexpectedResponse(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Argument 1 passed to Solido\Atlante\Requester\Response\ResponseFactory::fromResponse must be an instance of Psr\Http\Message\ResponseInterface, stdClass passed');

        $requesterResponse = [];
        $factory = new ResponseFactory();
        // @phpstan-ignore-next-line
        $factory->fromResponse((object) $requesterResponse);
    }

    /**
     * @param string[]|string[][] $headers
     */
    // @phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function mockSfResponse(int $statusCode, string $content, array $headers): SymfonyHttpClientResponse
    {
        $response = $this->prophesize(SymfonyHttpClientResponse::class);

        $response->getStatusCode()->willReturn($statusCode);
        $response->getHeaders(Argument::cetera())->willReturn($headers);
        $response->getContent(Argument::cetera())->willReturn($content);

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
        $response->getHeaders(Argument::cetera())->willReturn($headers);
        $response->getBody()->willReturn($content);

        $r = $response->reveal();
        assert($r instanceof PsrResponseInterface);

        return $r;
    }
}
