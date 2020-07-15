<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Decorator;

use Solido\Atlante\Requester\Request;
use function http_build_query;
use function parse_str;
use function rtrim;
use function Safe\array_replace_recursive;
use function Safe\parse_url;
use function strpos;
use function strtr;

class UrlDecorator implements DecoratorInterface
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function decorate(Request $request): Request
    {
        $url = $request->getUrl();

        $parsedUrl = parse_url($url);
        $parsedBase = parse_url($this->baseUrl);

        $url = strtr('schema://auth@host:port/path?query#fragment', [
            'schema://' => ($scheme = $parsedBase['scheme'] ?? null) !== null ? $scheme . '://' : '',
            'auth@' => ($user = $parsedBase['user'] ?? null) !== null && ($pass = $parsedBase['pass'] ?? null) !== null ?
                ($user . ':' . $pass . '@') : '',
            'host' => $parsedBase['host'] ?? '',
            ':port' => $parsedBase['port'] ?? null ? ':' . $parsedBase['port'] : '',
            '/path?query' => (static function () use ($parsedUrl, $parsedBase): string {
                $basePath = $parsedBase['path'] ?? null;
                $baseQuery = $parsedBase['query'] ?? null;
                $urlPath = $parsedUrl['path'] ?? null;
                $urlQuery = $parsedUrl['query'] ?? null;

                if ($basePath !== null && $urlPath !== null) {
                    // keep baseurl path if has any queries, combine otherwise (path prefix)
                    $path = $baseQuery ? $basePath : rtrim($basePath, '/') . $urlPath;
                } else {
                    $path = $basePath . $urlPath;
                }

                if ($baseQuery === null || $urlQuery === null) {
                    $query = $baseQuery . $urlQuery;
                } else {
                    $bq = $uq = [];
                    parse_str($baseQuery, $bq);
                    parse_str($urlQuery, $uq);
                    $query = http_build_query(array_replace_recursive($bq, $uq));
                }

                return (strpos($path, '/') === 0 ? '' : '/') . $path . ($query !== '' ? '?' : '') . $query;
            })(),
            '#fragment' => ($fragment = $parsedUrl['fragment'] ?? $parsedBase['fragment'] ?? null) === null ? '' :
                '#' . $fragment,
        ]);

        return new Request($request->getMethod(), $url, $request->getHeaders(), $request->getBody());
    }
}
