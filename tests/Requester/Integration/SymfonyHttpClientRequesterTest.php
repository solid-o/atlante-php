<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Integration;

use PHPUnit\Framework\Attributes\Group;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\SymfonyHttpClientRequester;
use Symfony\Component\HttpClient\HttpClient;

#[Group('integration')]
class SymfonyHttpClientRequesterTest extends AbstractClientRequesterTest
{
    protected function createRequester(): RequesterInterface
    {
        return new SymfonyHttpClientRequester(HttpClient::create());
    }
}
