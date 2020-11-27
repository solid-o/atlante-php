<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use JsonException;
use Solido\Atlante\Http\HeaderBag;

use function assert;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function strpos;

use const JSON_THROW_ON_ERROR;

abstract class AbstractResponseFactory implements ResponseFactoryInterface
{
    protected function makeResponse(int $statusCode, HeaderBag $headers, string $body): ResponseInterface
    {
        $data = static::decodeData($headers, $body);

        if (is_array($data) || is_object($data)) {
            if ($statusCode < 300 && $statusCode >= 200) {
                return new Response($statusCode, $headers, $data);
            }

            switch ($statusCode) {
                case 400:
                    return new BadResponse($headers, $data);

                case 403:
                    return new AccessDeniedResponse($headers, $data);

                case 404:
                    return new NotFoundResponse($headers, $data);
            }
        }

        return new InvalidResponse($statusCode, $headers, $data);
    }

    /**
     * @return mixed[]|object|string
     */
    private static function decodeData(HeaderBag $headers, string $data)
    {
        $contentType = $headers->get('content-type', 'text/html');
        assert(is_string($contentType));

        if (strpos($contentType, 'application/json') === 0) {
            try {
                $data = json_decode($data, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                // @ignoreException
            }
        }

        return $data;
    }
}
