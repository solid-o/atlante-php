<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

class NotFoundResponse extends InvalidResponse
{
    private const HTTP_STATUS = 404;

    /**
     * @param mixed[]|object|string $data
     */
    public function __construct(HeaderBag $headers, $data)
    {
        parent::__construct(self::HTTP_STATUS, $headers, $data);
    }
}
