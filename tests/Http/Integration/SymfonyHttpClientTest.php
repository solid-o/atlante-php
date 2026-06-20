<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http\Integration;

use PHPUnit\Framework\Attributes\Group;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\SymfonyHttpClientRequester;
use Symfony\Component\HttpClient\HttpClient;

#[Group("integration")]
class SymfonyHttpClientTest extends AbstractClientTest
{
    protected function createRequester(): RequesterInterface
    {
        return new SymfonyHttpClientRequester(HttpClient::create());
    }
}
