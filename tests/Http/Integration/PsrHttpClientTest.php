<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Http\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Solido\Atlante\Requester\PsrClientRequester;
use Solido\Atlante\Requester\RequesterInterface;

/**
 * @group integration
 */
class PsrHttpClientTest extends AbstractClientTest
{
    protected function createRequester(): RequesterInterface
    {
        return new PsrClientRequester(new Client(), new HttpFactory(), new HttpFactory());
    }
}
