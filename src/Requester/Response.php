<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Safe\Exceptions\JsonException;
use Solido\Atlante\Http\HeaderBag;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyHttpClientResponse;
use TypeError;
use function get_debug_type;
use function Safe\json_decode;
use function Safe\sprintf;
use function strpos;

class Response
{
    /** @var string|object */
    private $data;
    private int $status;

    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     */
    public static function fromResponse($response): self
    {
        if ($response instanceof PsrResponseInterface || $response instanceof SymfonyHttpClientResponse) {
            return new self($response);
        }

        throw new TypeError(sprintf('Argument 1 passed to %s has to be an instance of Psr\Http\Message\ResponseInterface or Symfony\Contracts\HttpClient\ResponseInterface, %s passed', __METHOD__, get_debug_type($response)));
    }

    /** @return string|object */
    public function getData()
    {
        return $this->data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param PsrResponseInterface|SymfonyHttpClientResponse $response
     */
    private function __construct($response)
    {
        $this->status = $response->getStatusCode();
        if ($response instanceof PsrResponseInterface) {
            $data = (string) $response->getBody();
            $headers = $response->getHeaders();
        } elseif ($response instanceof SymfonyHttpClientResponse) {
            $data = $response->getContent(false);
            $headers = $response->getHeaders(false);
        } else {
            throw new TypeError(sprintf('Argument 1 passed to %s has to be an instance of Psr\Http\Message\ResponseInterface or Symfony\Contracts\HttpClient\ResponseInterface, %s passed', __METHOD__, get_debug_type($response)));
        }

        $headers = new HeaderBag($headers);
        $contentType = $headers->get('content-type', 'text/html');

        if ($contentType !== null && strpos($contentType, 'application/json') === 0) {
            try {
                $data = json_decode($data, false);
            } catch (JsonException $e) {
                // @ignoreException
            }
        }

        $this->data = $data;
    }
}
