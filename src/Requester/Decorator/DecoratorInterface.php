<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Requester\Request;

interface DecoratorInterface
{
    public function decorate(Request $request): Request;
}
