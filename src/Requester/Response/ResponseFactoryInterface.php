<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

interface ResponseFactoryInterface
{
    public function fromResponse(object $response): ResponseInterface;
}
