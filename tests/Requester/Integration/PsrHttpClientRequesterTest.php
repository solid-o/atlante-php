<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Solido\Atlante\Requester\PsrClientRequester;
use Solido\Atlante\Requester\RequesterInterface;

/**
 * @group integration
 */
class PsrHttpClientRequesterTest extends AbstractClientRequesterTest
{
    protected function createRequester(): RequesterInterface
    {
        return new PsrClientRequester(new Client(), new HttpFactory(), new HttpFactory());
    }
}
