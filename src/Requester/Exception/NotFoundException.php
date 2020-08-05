<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\NotFoundResponse;
use function assert;

class NotFoundException extends AbstractException
{
    public function getResponse(): NotFoundResponse
    {
        assert($this->response instanceof NotFoundResponse);

        return $this->response;
    }
}
