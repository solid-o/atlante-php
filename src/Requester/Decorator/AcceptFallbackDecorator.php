<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;

class AcceptFallbackDecorator implements DecoratorInterface
{
    private string $accept;

    public function __construct(?string $accept = null)
    {
        $this->accept = $accept ?? 'application/json';
    }

    public function decorate(Request $request): Request
    {
        $headers = new HeaderBag($request->getHeaders());
        $headers->set('accept', $this->accept, true);

        return new Request($request->getMethod(), $request->getUrl(), $headers->all(), $request->getBody());
    }
}
