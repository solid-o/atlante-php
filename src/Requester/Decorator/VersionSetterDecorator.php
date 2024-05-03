<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;

class VersionSetterDecorator implements DecoratorInterface
{
    public function __construct(private string $version)
    {
    }

    public function decorate(Request $request): Request
    {
        $headers = new HeaderBag($request->getHeaders());
        $headers->set('version', $this->version);

        return new Request($request->getMethod(), $request->getUrl(), $headers->all(), $request->getBody());
    }
}
