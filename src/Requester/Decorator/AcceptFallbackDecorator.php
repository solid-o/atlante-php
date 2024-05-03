<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Http\HeaderBag;
use Solido\Atlante\Requester\Request;

class AcceptFallbackDecorator implements DecoratorInterface
{
    private string $accept;

    public function __construct(string|null $accept = null)
    {
        $this->accept = $accept ?? 'application/json';
    }

    public function decorate(Request $request): Request
    {
        $headers = new HeaderBag($request->getHeaders());
        if (! $headers->has('accept')) {
            $headers->set('accept', $this->accept);
        }

        return new Request($request->getMethod(), $request->getUrl(), $headers->all(), $request->getBody());
    }
}
