<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Group;
use Solido\Atlante\Requester\PsrClientRequester;
use Solido\Atlante\Requester\RequesterInterface;

#[Group('integration')]
class PsrHttpClientRequesterTest extends AbstractClientRequesterTest
{
    protected function createRequester(): RequesterInterface
    {
        return new PsrClientRequester(new Client(), new HttpFactory(), new HttpFactory());
    }
}
