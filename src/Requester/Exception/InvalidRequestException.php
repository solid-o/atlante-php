<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\InvalidResponse;
use Solido\Atlante\Requester\Response\ResponseInterface;

class InvalidRequestException extends AbstractException
{
    /** @var InvalidResponse */
    protected ResponseInterface $response;

    public function __construct(InvalidResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse(): InvalidResponse
    {
        return $this->response;
    }
}
