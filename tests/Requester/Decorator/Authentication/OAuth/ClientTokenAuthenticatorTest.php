<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Decorator\Authentication\OAuth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Solido\Atlante\Exception\NoTokenAvailableException;
use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Decorator\Authentication\OAuth\ClientTokenAuthenticator;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\Response;
use Solido\Atlante\Storage\AbstractStorage;
use Solido\Atlante\Storage\PsrCacheStorage;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ClientTokenAuthenticatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|RequesterInterface */
    private $requester;
    private PsrCacheStorage $storage;

    protected function setUp(): void
    {
        $this->requester = $this->prophesize(RequesterInterface::class);
        $this->storage = new PsrCacheStorage(new ArrayAdapter());
    }

    public function testShouldNotDecorateIfRequestAlreadyHasAnAuthorizationHeader(): void
    {
        $request = new Request('GET', '/', ['Authorization' => 'Basic testtest']);

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => '', 'client_id' => '']);
        $request = $decorator->decorate($request);
        self::assertEquals(['Basic testtest'], $request->getHeaders()['authorization']);
    }

    public function testShouldUseCachedToken(): void
    {
        $item = $this->storage->getItem('solido_atlante_client_token');
        $item->set('cached_token');
        $item->expiresAfter(3600);
        $this->storage->save($item);

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => '', 'client_id' => '']);
        $request = $decorator->decorate(new Request('GET', '/'));
        self::assertEquals(['Bearer cached_token'], $request->getHeaders()['authorization']);

        $this->storage->clear();
        $item = $this->storage->getItem('client_token');
        $item->set('cached_token2');
        $item->expiresAfter(3600);
        $this->storage->save($item);

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => '', 'client_id' => '', 'client_token_key' => 'client_token']);
        $request = $decorator->decorate(new Request('GET', '/'));
        self::assertEquals(['Bearer cached_token2'], $request->getHeaders()['authorization']);
    }

    public function testShouldRequestATokenWithJsonEncoding(): void
    {
        $this->requester->request('POST', 'http://localhost/token', ['Content-Type' => 'application/json'], '{"grant_type":"client_credentials","client_id":"id","client_secret":"secret"}')
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), (object) ['access_token' => 'access_token', 'expires_in' => 3600]));

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => 'http://localhost/token', 'client_id' => 'id', 'client_secret' => 'secret']);
        $request = $decorator->decorate(new Request('GET', '/'));
        self::assertEquals(['Bearer access_token'], $request->getHeaders()['authorization']);
        self::assertTrue($this->storage->hasItem('solido_atlante_client_token'));

        $item = $this->storage->getItem('solido_atlante_client_token');
        self::assertTrue($item->isHit());
        self::assertEquals('access_token', $item->get());

        $expiration = (fn () => ($this->getExpiration)($item))->bindTo($this->storage, AbstractStorage::class)();
        self::assertTrue($expiration <= new DateTimeImmutable('+59 minutes'));
    }

    public function testShouldRequestATokenWithFormEncoding(): void
    {
        $this->requester->request('POST', 'http://localhost/token', ['Content-Type' => 'application/x-www-form-urlencoded'], 'grant_type=client_credentials&client_id=id&client_secret=secret')
            ->shouldBeCalled()
            ->willReturn(new Response(200, new HeaderBag(), (object) ['access_token' => 'access_token', 'expires_in' => 3600]));

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => 'http://localhost/token', 'client_id' => 'id', 'client_secret' => 'secret', 'data_encoding' => 'form']);
        $request = $decorator->decorate(new Request('GET', '/'));
        self::assertEquals(['Bearer access_token'], $request->getHeaders()['authorization']);
        self::assertTrue($this->storage->hasItem('solido_atlante_client_token'));

        $item = $this->storage->getItem('solido_atlante_client_token');
        self::assertTrue($item->isHit());
        self::assertEquals('access_token', $item->get());

        $expiration = (fn () => ($this->getExpiration)($item))->bindTo($this->storage, AbstractStorage::class)();
        self::assertTrue($expiration <= new DateTimeImmutable('+59 minutes'));
    }

    public function testShouldThrowIfTokenRequestFails(): void
    {
        $this->requester->request('POST', 'http://localhost/token', Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(new Response(500, new HeaderBag(), (object) ['error' => 'bad error!']));

        $this->expectException(NoTokenAvailableException::class);
        $this->expectExceptionMessage('Client credentials token returned status 500');

        $decorator = new ClientTokenAuthenticator($this->requester->reveal(), $this->storage, ['token_endpoint' => 'http://localhost/token', 'client_id' => 'id']);
        $decorator->getToken();
    }
}
