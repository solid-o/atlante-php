<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\InvalidResponse;

use function assert;

class InvalidRequestException extends AbstractException
{
    public function __construct(InvalidResponse $response)
    {
        parent::__construct($response);
    }

    public function getResponse(): InvalidResponse
    {
        assert($this->response instanceof InvalidResponse);

        return $this->response;
    }
}
