<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

interface ResponseInterface
{
    /**
     * Returns the API response content.
     *
     * @return object|string
     */
    public function getData();

    /**
     * Gets the HTTP status code of the response.
     */
    public function getStatusCode(): int;
}
