<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

use function assert;

class BadResponse extends InvalidResponse
{
    private const HTTP_STATUS = 400;

    /**
     * @param mixed[]|object|string $data
     */
    public function __construct(HeaderBag $headers, $data)
    {
        $data = BadResponsePropertyTree::parse($data);

        parent::__construct(self::HTTP_STATUS, $headers, $data);
    }

    /**
     * Alias of getData().
     */
    public function getErrors(): BadResponsePropertyTree
    {
        return $this->getData();
    }

    public function getData(): BadResponsePropertyTree
    {
        $data = parent::getData();
        assert($data instanceof BadResponsePropertyTree);

        return $data;
    }
}
