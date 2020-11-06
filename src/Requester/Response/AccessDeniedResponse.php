<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

class AccessDeniedResponse extends InvalidResponse
{
    private const HTTP_STATUS = 403;

    /**
     * @param object|string $data
     * @param array<string, string|string[]> $headers
     */
    public function __construct($data, array $headers)
    {
        parent::__construct(self::HTTP_STATUS, $headers, $data);
    }
}
