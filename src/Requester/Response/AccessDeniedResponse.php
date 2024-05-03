<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

class AccessDeniedResponse extends InvalidResponse
{
    private const HTTP_STATUS = 403;

    public function __construct(HeaderBag $headers, mixed $data)
    {
        parent::__construct(self::HTTP_STATUS, $headers, $data);
    }
}
