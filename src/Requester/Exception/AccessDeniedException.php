<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\AccessDeniedResponse;
use function assert;

class AccessDeniedException extends AbstractException
{
    public function getResponse(): AccessDeniedResponse
    {
        assert($this->response instanceof AccessDeniedResponse);

        return $this->response;
    }
}
