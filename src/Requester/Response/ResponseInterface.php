<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Solido\Atlante\Http\HeaderBag;

interface ResponseInterface
{
    /**
     * Returns the API response content.
     *
     * @return object|string
     */
    public function getData();

    /**
     * Gets the HTTP headers received in the response.
     */
    public function getHeaders(): HeaderBag;

    /**
     * Gets the HTTP status code of the response.
     */
    public function getStatusCode(): int;
}
