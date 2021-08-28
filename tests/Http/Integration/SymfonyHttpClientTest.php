<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http\Integration;

use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\SymfonyHttpClientRequester;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
class SymfonyHttpClientTest extends AbstractClientTest
{
    protected function createRequester(): RequesterInterface
    {
        return new SymfonyHttpClientRequester(HttpClient::create());
    }
}
