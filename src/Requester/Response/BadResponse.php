<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use function assert;

class BadResponse extends InvalidResponse
{
    private const HTTP_STATUS = 400;

    /** @var BadResponsePropertyTree */
    protected $data;

    /**
     * @param array<string, string|string[]> $headers
     * @param mixed[]|object|string $data
     */
    public function __construct(array $headers, $data)
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
