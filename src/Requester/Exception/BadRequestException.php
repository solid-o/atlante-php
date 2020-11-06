<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\BadResponse;

use function assert;

class BadRequestException extends AbstractException
{
    public function __construct(BadResponse $response)
    {
        parent::__construct($response);
    }

    public function getResponse(): BadResponse
    {
        assert($this->response instanceof BadResponse);

        return $this->response;
    }
}
