<?php

declare(strict_types=1);

namespace Solido\Atlante\Http;

use function addcslashes;
use function array_slice;
use function count;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function strtolower;
use function substr;

use const PREG_SET_ORDER;

/**
 * HTTP header utility functions.
 */
class HeaderUtils
{
    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Splits an HTTP header by one or more separators.
     *
     * Example:
     *
     *     HeaderUtils::split("da, en-gb;q=0.8", ",;")
     *     // => ['da'], ['en-gb', 'q=0.8']]
     *
     * @param string $separators List of characters to split on, ordered by
     *                           precedence, e.g. ",", ";=", or ",;="
     *
     * @return array<int, string|mixed> Nested array with as many levels as there are characters in $separators
     */
    public static function split(string $header, string $separators): array
    {
        $quotedSeparators = preg_quote($separators, '/');

        preg_match_all('
            /
                (?!\s)
                    (?:
                        # quoted-string
                        "(?:[^"\\\\]|\\\\.)*(?:"|\\\\|$)
                    |
                        # token
                        [^"' . $quotedSeparators . ']+
                    )+
                (?<!\s)
            |
                # separator
                \s*
                (?<separator>[' . $quotedSeparators . '])
                \s*
            /x', $header, $matches, PREG_SET_ORDER);

        return self::groupParts($matches, $separators);
    }

    /**
     * Combines an array of arrays into one associative array.
     *
     * Each of the nested arrays should have one or two elements. The first
     * value will be used as the keys in the associative array, and the second
     * will be used as the values, or true if the nested array only contains one
     * element. Array keys are lowercased.
     *
     * Example:
     *
     *     HeaderUtils::combine([["foo", "abc"], ["bar"]])
     *     // => ["foo" => "abc", "bar" => true]
     *
     * @param mixed[] $parts
     *
     * @return array<string, mixed>
     */
    public static function combine(array $parts): array
    {
        $assoc = [];
        foreach ($parts as $part) {
            $name = strtolower($part[0]);
            $value = $part[1] ?? true;
            $assoc[$name] = $value;
        }

        return $assoc;
    }

    /**
     * Joins an associative array into a string for use in an HTTP header.
     *
     * The key and value of each entry are joined with "=", and all entries
     * are joined with the specified separator and an additional space (for
     * readability). Values are quoted if necessary.
     *
     * Example:
     *
     *     HeaderUtils::toString(["foo" => "abc", "bar" => true, "baz" => "a b c"], ",")
     *     // => 'foo=abc, bar, baz="a b c"'
     *
     * @param array<string, mixed> $assoc
     */
    public static function toString(array $assoc, string $separator): string
    {
        $parts = [];
        foreach ($assoc as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } else {
                $parts[] = $name . '=' . self::quote((string) $value);
            }
        }

        return implode($separator . ' ', $parts);
    }

    /**
     * Encodes a string as a quoted string, if necessary.
     *
     * If a string contains characters not allowed by the "token" construct in
     * the HTTP specification, it is backslash-escaped and enclosed in quotes
     * to match the "quoted-string" construct.
     */
    public static function quote(string $s): string
    {
        if (preg_match('/^[a-z0-9!#$%&\'*.^_`|~-]+$/i', $s)) {
            return $s;
        }

        return '"' . addcslashes($s, '"\\"') . '"';
    }

    /**
     * Decodes a quoted string.
     *
     * If passed an unquoted string that matches the "token" construct (as
     * defined in the HTTP specification), it is passed through verbatimly.
     */
    public static function unquote(string $s): string
    {
        /** @phpstan-ignore-next-line */
        return preg_replace('/\\\\(.)|"/', '$1', $s);
    }

    /**
     * @param string[][]  $matches
     *
     * @return array<int, string|mixed>
     */
    private static function groupParts(array $matches, string $separators, bool $first = true): array
    {
        $separator = $separators[0];
        $partSeparators = substr($separators, 1);

        $i = 0;
        $partMatches = [];
        $previousMatchWasSeparator = false;
        foreach ($matches as $match) {
            $currentIsSeparator = isset($match['separator']) && $match['separator'] === $separator;
            if ($currentIsSeparator) {
                if (! $first && $previousMatchWasSeparator) {
                    $partMatches[$i][] = $match;
                }

                $previousMatchWasSeparator = true;
                ++$i;
            } else {
                $previousMatchWasSeparator = false;
                $partMatches[$i][] = $match;
            }
        }

        $parts = [];
        if ($partSeparators) {
            foreach ($partMatches as $matches) {
                $parts[] = self::groupParts($matches, $partSeparators, false);
            }
        } else {
            foreach ($partMatches as $matches) {
                $parts[] = self::unquote($matches[0][0]);
            }

            if (! $first && 2 < count($parts)) {
                $parts = [
                    $parts[0],
                    implode($separator, array_slice($parts, 1)),
                ];
            }
        }

        return $parts;
    }
}
