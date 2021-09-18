<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response\Parser\BadResponse;

use Solido\Atlante\Requester\Response\BadResponsePropertyTree;

interface BadResponsePropertyTreeParserInterface
{
    /**
     * Whether the current parser supports the passed data.
     *
     * @param mixed $data
     */
    public function supports($data): bool;

    /**
     * @param mixed $data
     */
    public function parse($data): BadResponsePropertyTree;
}
