<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

class NotFoundResponse extends InvalidResponse
{
    private const HTTP_STATUS = 404;

    /**
     * @param array<string, string|string[]> $headers
     * @param mixed[]|object|string $data
     */
    public function __construct(array $headers, $data)
    {
        parent::__construct(self::HTTP_STATUS, $headers, $data);
    }
}
