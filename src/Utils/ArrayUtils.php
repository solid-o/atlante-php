<?php

declare(strict_types=1);

namespace Solido\Atlante\Utils;

use function is_array;
use function strpos;
use function Symfony\Component\String\u;

class ArrayUtils
{
    /**
     * Recursively converts all the keys to camel case.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function toCamelCaseKeys(array $input): array
    {
        $res = [];
        foreach ($input as $key => $value) {
            if (strpos($key, '_') === 0) {
                $key = '_' . u($key)->camel();
            } else {
                $key = u($key)->camel();
            }

            if (is_array($value)) {
                $value = self::toCamelCaseKeys($value);
            }

            $res[(string) $key] = $value;
        }

        return $res;
    }

    /**
     * Recursively converts all the keys to snake case.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function toSnakeCaseKeys(array $input): array
    {
        $res = [];
        foreach ($input as $key => $value) {
            if (strpos($key, '_') === 0) {
                $key = '_' . u($key)->snake();
            } else {
                $key = u($key)->snake();
            }

            if (is_array($value)) {
                $value = self::toSnakeCaseKeys($value);
            }

            $res[(string) $key] = $value;
        }

        return $res;
    }
}
