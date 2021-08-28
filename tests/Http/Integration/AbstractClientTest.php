<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http\Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Http\Client;
use Solido\Atlante\Requester\Decorator\AcceptFallbackDecorator;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Decorator\UrlDecorator;
use Solido\Atlante\Requester\Exception\InvalidRequestException;
use Solido\Atlante\Requester\Exception\NotFoundException;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Tests\Fixtures\TestHttpServer;
use Symfony\Component\Process\Process;

use function Safe\fopen;

/**
 * @group integration
 */
abstract class AbstractClientTest extends TestCase
{
    private static Process $server;
    private RequesterInterface $requester;
    private Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = TestHttpServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function setUp(): void
    {
        $this->requester = $this->createRequester();
        $this->client = new Client($this->requester, [
            new UrlDecorator('http://localhost:8057'),
            new AcceptFallbackDecorator(),
            new BodyConverterDecorator(),
        ]);
    }

    public function testGetMethod(): void
    {
        $response = $this->client->get('/', []);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('GET', $response->getData()->REQUEST_METHOD);
        self::assertEquals('application/json', $response->getData()->HTTP_ACCEPT);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    public function testPostMethodWithStringBody(): void
    {
        $response = $this->client->post('/post', '{"test":"foobar"}', ['content-type' => 'application/json']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithArrayBody(): void
    {
        $response = $this->client->post('/post', ['test' => 'foobar'], ['content-type' => 'application/json']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithStreamBody(): void
    {
        $response = $this->client->post('/post', fopen('data://text/plain,{"test":"foobar"}', 'rb'), ['content-type' => 'application/json']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithGeneratorBody(): void
    {
        $response = $this->client->post('/post', static function () {
            yield 'test' => 'foobar';
        }, ['content-type' => 'application/json']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function test404(): void
    {
        $this->expectException(NotFoundException::class);
        $this->client->get('/404');
    }

    public function testShouldNotFollowRedirects(): void
    {
        try {
            $this->client->get('/302');

            throw new Exception('Should not reach this point');
        } catch (InvalidRequestException $e) {
            $response = $e->getResponse();
        }

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('http://localhost:8057/', $response->getHeaders()->get('location'));
        self::assertEquals('GET', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    public function testShouldNotFollow307Redirects(): void
    {
        try {
            $this->client->post('/307');

            throw new Exception('Should not reach this point');
        } catch (InvalidRequestException $e) {
            $response = $e->getResponse();
        }

        self::assertEquals(307, $response->getStatusCode());
        self::assertEquals('http://localhost:8057/post', $response->getHeaders()->get('location'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    abstract protected function createRequester(): RequesterInterface;
}
