<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response\Parser\BadResponse;

use Solido\Atlante\Requester\Response\BadResponsePropertyTree;

interface BadResponsePropertyTreeParserInterface
{
    /**
     * Whether the current parser supports the passed data.
     */
    public function supports(mixed $data): bool;

    public function parse(mixed $data): BadResponsePropertyTree;
}
