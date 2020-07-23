<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Exception;

use RuntimeException;
use Solido\Atlante\Requester\Response\ResponseInterface;

abstract class AbstractException extends RuntimeException
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
