<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Requester\Request;

use function array_replace_recursive;
use function assert;
use function http_build_query;
use function parse_str;
use function parse_url;
use function strrpos;
use function strtr;
use function substr;

class UrlDecorator implements DecoratorInterface
{
    public function __construct(private string $baseUrl)
    {
    }

    public function decorate(Request $request): Request
    {
        $url = $request->getUrl();

        $parsedUrl = parse_url($url);
        $parsedBase = parse_url($this->baseUrl);
        if (isset($parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['path'])) {
            return new Request($request->getMethod(), $url, $request->getHeaders(), $request->getBody());
        }

        $url = strtr('schema://auth@host:port/path?query#fragment', [
            'schema://' => ($scheme = $parsedBase['scheme'] ?? null) !== null ? $scheme . '://' : '',
            'auth@' => (static function () use ($parsedBase): string {
                $user = $parsedBase['user'] ?? null;
                if ($user === null) {
                    return '';
                }

                $auth = $user;
                $pass = $parsedBase['pass'] ?? null;
                if ($pass !== null) {
                    $auth .= ':' . $pass;
                }

                return $auth . '@';
            })(),
            'host' => $parsedBase['host'] ?? '',
            ':port' => $parsedBase['port'] ?? null ? ':' . $parsedBase['port'] : '',
            '/path?query#fragment' => (static function () use ($parsedUrl, $parsedBase): string {
                $basePath = $parsedBase['path'] ?? '/';
                $baseQuery = $parsedBase['query'] ?? '';
                $baseFragment = $parsedBase['fragment'] ?? '';
                $urlPath = $parsedUrl['path'] ?? null;
                $urlQuery = $parsedUrl['query'] ?? null;
                $urlFragment = $parsedUrl['fragment'] ?? null;

                $path = $basePath;
                $query = $baseQuery;
                $fragment = $baseFragment;
                if ($urlPath !== null) {
                    if (($urlPath[0] ?? null) === '/') {
                        return $urlPath .
                            (($urlQuery ?? '') !== '' ? '?' : '') . $urlQuery .
                            (($urlFragment ?? '') !== '' ? '#' : '') . $urlFragment;
                    }

                    if ($basePath !== null) {
                        $pos = strrpos($basePath, '/');
                        assert($pos !== false);
                        $path = substr($basePath, 0, $pos) . '/' . $urlPath;
                    }

                    $query = $urlQuery ?? '';
                    $fragment = $urlFragment ?? '';
                } else {
                    if ($urlQuery !== null) {
                        $bq = $uq = [];
                        parse_str($baseQuery, $bq);
                        parse_str($urlQuery, $uq);
                        $query = http_build_query(array_replace_recursive($bq, $uq));
                    }

                    if ($urlFragment !== null) {
                        $fragment = $urlFragment;
                    }
                }

                assert($path[0] === '/');

                return $path . ($query !== '' ? '?' : '') . $query .
                    ($fragment !== '' ? '#' : '') . $fragment;
            })(),
        ]);

        return new Request($request->getMethod(), $url, $request->getHeaders(), $request->getBody());
    }
}
