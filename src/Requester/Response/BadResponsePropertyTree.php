<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use InvalidArgumentException;
use function array_map;
use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

final class BadResponsePropertyTree
{
    /** @var string[] */
    private array $errors = [];

    private string $name;

    /** @var static[] */
    private array $children = [];

    /**
     * @param object|array<string,mixed>|string $content
     */
    public static function parse($content): self
    {
        $obj = new self();

        if (is_string($content)) {
            throw new InvalidArgumentException('Unexpected response type, object or array expected, string given');
        }

        $content = (array) $content;

        $errors = $content['errors'] ?? null;
        if ($errors === null) {
            throw new InvalidArgumentException('Unable to parse missing `errors` property');
        }

        if (! is_array($errors)) {
            throw new InvalidArgumentException(sprintf('Invalid `errors` property type, expected array, %s given', get_debug_type($errors)));
        }

        $obj->errors = $errors;

        $name = $content['name'] ?? null;
        if ($name === null) {
            throw new InvalidArgumentException('Missing `name` property');
        }

        if (! is_string($name)) {
            throw new InvalidArgumentException(sprintf('Invalid `name` property type, expected string, %s given', get_debug_type($name)));
        }

        $obj->name = $name;

        $children = $content['children'] ?? [];
        if (! is_array($children)) {
            throw new InvalidArgumentException(sprintf('Invalid `children` property type, expected array, %s given', get_debug_type($children)));
        }

        $obj->children = array_map([$obj, 'parse'], $children);

        return $obj;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return self[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @internal Use static `parse` method instead
     */
    private function __construct()
    {
    }
}
