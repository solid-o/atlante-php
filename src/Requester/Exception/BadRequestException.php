<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use Solido\Atlante\Requester\Response\BadResponse;
use Solido\Atlante\Requester\Response\ResponseInterface;

class BadRequestException extends AbstractException
{
    /** @var BadResponse */
    protected ResponseInterface $response;

    public function __construct(BadResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse(): BadResponse
    {
        return $this->response;
    }
}
