<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use JsonException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Solido\Atlante\Http\HeaderBag;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyHttpClientResponse;
use TypeError;

use function assert;
use function get_debug_type;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function Safe\sprintf;
use function strpos;

use const JSON_THROW_ON_ERROR;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     */
    public function fromResponse($response): ResponseInterface
    {
        $data = static::decodeData($response);
        $statusCode = $response->getStatusCode();

        if ($response instanceof SymfonyHttpClientResponse) {
            $headers = $response->getHeaders(false);
        } else {
            $headers = $response->getHeaders();
        }

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
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     *
     * @return mixed[]|object|string
     */
    protected static function decodeData($response)
    {
        if ($response instanceof PsrResponseInterface) {
            $data = (string) $response->getBody();
            $headers = new HeaderBag($response->getHeaders());
        } elseif ($response instanceof SymfonyHttpClientResponse) {
            $data = $response->getContent(false);
            $headers = new HeaderBag($response->getHeaders(false));
        } else {
            throw new TypeError(sprintf('Argument 1 passed to %s has to be an instance of Psr\Http\Message\ResponseInterface or Symfony\Contracts\HttpClient\ResponseInterface, %s passed', __METHOD__, get_debug_type($response)));
        }

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
