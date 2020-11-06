<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator\Authentication\OAuth;

use Solido\Atlante\Exception\NoTokenAvailableException;
use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Request;
use Solido\Atlante\Requester\RequesterInterface;
use Solido\Atlante\Requester\Response\ResponseInterface;
use Solido\Atlante\Storage\StorageInterface;

use function assert;
use function http_build_query;
use function is_object;
use function Safe\json_encode;
use function Safe\sprintf;

class ClientTokenAuthenticator implements DecoratorInterface
{
    private RequesterInterface $requester;
    private StorageInterface $tokenStorage;

    private string $tokenEndpoint;
    private string $clientId;
    private ?string $clientSecret;
    private string $clientTokenKey;

    /** @phpstan-var 'json'|'form' */
    private string $dataEncoding;

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-param array{token_endpoint: string, client_id: string, client_secret?: string|null, client_token_key?: string, data_encoding: 'json'|'form'} $options
     */
    public function __construct(RequesterInterface $requester, StorageInterface $storage, array $options)
    {
        $this->requester = $requester;
        $this->tokenStorage = $storage;

        $this->tokenEndpoint = $options['token_endpoint'];
        $this->clientId = $options['client_id'];
        $this->clientSecret = $options['client_secret'] ?? null;
        $this->clientTokenKey = $options['client_token_key'] ?? 'solido_atlante_client_token';
        $this->dataEncoding = $options['data_encoding'] ?? 'json';
    }

    public function decorate(Request $request): Request
    {
        $headers = $request->getHeaders();
        if (! isset($headers['authorization'])) {
            $headers['authorization'] = 'Bearer ' . $this->getToken();
        }

        return new Request($request->getMethod(), $request->getUrl(), $headers, $request->getBody());
    }

    public function getToken(): string
    {
        $item = $this->tokenStorage->getItem($this->clientTokenKey);
        if ($item->isHit()) {
            return $item->get();
        }

        [$body, $headers] = $this->buildTokenRequest(['grant_type' => 'client_credentials']);
        $response = $this->request($body, $headers);

        if ($response->getStatusCode() !== 200) {
            throw new NoTokenAvailableException(sprintf('Client credentials token returned status %d', $response->getStatusCode()));
        }

        $content = $response->getData();
        // @phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        assert(is_object($content) && isset($content->access_token, $content->expires_in));

        // @phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $token = $content->access_token;

        $item->set($token);
        // @phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        $item->expiresAfter($content->expires_in - 60);
        $this->tokenStorage->save($item);

        return $token;
    }

    /**
     * @param array<string, string> $options
     *
     * @return array<array<string, mixed>>
     *
     * @phpstan-return array{array<string, string>, array<string, string|string[]>}
     */
    protected function buildTokenRequest(array $options): array
    {
        $options['client_id'] = $this->clientId;

        if ($this->clientSecret !== null) {
            $options['client_secret'] = $this->clientSecret;
        }

        return [$options, []];
    }

    /**
     * @param array<string, string>|null $body
     * @param array<string, string|string[]> $headers
     */
    private function request(?array $body, array $headers): ResponseInterface
    {
        if ($this->dataEncoding === 'json') {
            $headers['Content-Type'] = 'application/json';
            $data = json_encode($body);
        } else {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $data = http_build_query($body ?? []);
        }

        return $this->requester->request('POST', $this->tokenEndpoint, $headers, $data);
    }
}
