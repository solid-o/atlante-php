<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Integration;

use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Decorator\BodyConverterDecorator;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Tests\Fixtures\TestHttpServer;
use Symfony\Component\Process\Process;

use function fopen;

/**
 * @group integration
 */
abstract class AbstractClientRequesterTest extends TestCase
{
    private static Process $server;
    private RequesterInterface $requester;

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
    }

    public function testGetMethod(): void
    {
        $response = $this->requester->request('GET', 'http://localhost:8057', []);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('GET', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    public function testPostMethodWithStringBody(): void
    {
        $response = $this->requester->request('POST', 'http://localhost:8057/post', ['content-type' => 'application/json'], '{"test":"foobar"}');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithArrayBody(): void
    {
        $decorator = new BodyConverterDecorator();
        $request = $decorator->decorate(new Request('POST', 'http://localhost:8057/post', ['content-type' => 'application/json'], ['test' => 'foobar']));
        $response = $this->requester->request($request->getMethod(), $request->getUrl(), $request->getHeaders(), $request->getBody());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithStreamBody(): void
    {
        $decorator = new BodyConverterDecorator();
        $request = $decorator->decorate(new Request('POST', 'http://localhost:8057/post', ['content-type' => 'application/json'], fopen('data://text/plain,{"test":"foobar"}', 'rb')));
        $response = $this->requester->request($request->getMethod(), $request->getUrl(), $request->getHeaders(), $request->getBody());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function testPostMethodWithGeneratorBody(): void
    {
        $decorator = new BodyConverterDecorator();
        $request = $decorator->decorate(new Request('POST', 'http://localhost:8057/post', ['content-type' => 'application/json'], static function () {
            yield 'test' => 'foobar';
        }));
        $response = $this->requester->request($request->getMethod(), $request->getUrl(), $request->getHeaders(), $request->getBody());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('foobar', $response->getData()->test);
    }

    public function test404(): void
    {
        $response = $this->requester->request('GET', 'http://localhost:8057/404', []);
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaders()->get('content-type'));
        self::assertEquals('GET', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    public function testShouldNotFollowRedirects(): void
    {
        $response = $this->requester->request('GET', 'http://localhost:8057/302', []);
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('http://localhost:8057/', $response->getHeaders()->get('location'));
        self::assertEquals('GET', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    public function testShouldNotFollow307Redirects(): void
    {
        $response = $this->requester->request('POST', 'http://localhost:8057/307', []);
        self::assertEquals(307, $response->getStatusCode());
        self::assertEquals('http://localhost:8057/post', $response->getHeaders()->get('location'));
        self::assertEquals('POST', $response->getData()->REQUEST_METHOD);
        self::assertEquals('localhost:8057', $response->getData()->HTTP_HOST);
    }

    abstract protected function createRequester(): RequesterInterface;
}
