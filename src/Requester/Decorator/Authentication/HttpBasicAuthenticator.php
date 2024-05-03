<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator\Authentication;

use Solido\Atlante\Requester\Decorator\DecoratorInterface;
use Solido\Atlante\Requester\Request;

use function base64_encode;

class HttpBasicAuthenticator implements DecoratorInterface
{
    private string $auth;

    public function __construct(string $usernameOrEncodedAuth, string|null $password = null)
    {
        $this->auth = $password === null ? $usernameOrEncodedAuth : base64_encode($usernameOrEncodedAuth . (! empty($password) ? ':' . $password : ''));
    }

    /**
     * Decorates the request adding basic authentication header.
     */
    public function decorate(Request $request): Request
    {
        $headers = $request->getHeaders();
        if (! isset($headers['authorization'])) {
            $headers['authorization'] = 'Basic ' . $this->auth;
        }

        return new Request($request->getMethod(), $request->getUrl(), $headers, $request->getBody());
    }
}
