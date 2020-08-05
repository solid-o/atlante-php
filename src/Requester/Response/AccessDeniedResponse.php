<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

class AccessDeniedResponse extends InvalidResponse
{
    private const HTTP_STATUS = 403;

    /**
     * @param object|string $data
     */
    public function __construct($data)
    {
        parent::__construct(self::HTTP_STATUS, $data);
    }
}
