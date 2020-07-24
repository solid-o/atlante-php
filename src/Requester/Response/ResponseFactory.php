<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Safe\Exceptions\JsonException;
use Solido\Atlante\Http\HeaderBag;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyHttpClientResponse;
use TypeError;
use function assert;
use function get_debug_type;
use function is_object;
use function is_string;
use function Safe\json_decode;
use function Safe\sprintf;
use function strpos;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     */
    public function fromResponse($response): ResponseInterface
    {
        $statusCode = $response->getStatusCode();
        $data = static::decodeData($response);

        if (is_object($data)) {
            if ($statusCode < 300 && $statusCode >= 200) {
                return new Response($statusCode, $data);
            }

            if ($statusCode === 400) {
                return new BadResponse($data);
            }
        }

        return new InvalidResponse($statusCode, $data);
    }

    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     *
     * @return object|string
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
        if (strpos('application/json', $contentType) === 0) {
            try {
                $data = json_decode($data, false);
            } catch (JsonException $e) {
                // @ignoreException
            }
        }

        return $data;
    }
}