<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

use InvalidArgumentException;
use function array_map;
use function is_string;

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
            throw new InvalidArgumentException('Unexpcted response type, object or array expected, string given');
        }

        $content = (array) $content;

        $errors = $content['errors'] ?? null;
        if ($errors === null) {
            throw new InvalidArgumentException('Unable to parse missing `errors`');
        }

        $obj->errors = $errors;

        $name = $content['name'] ?? null;
        if ($name === null) {
            throw new InvalidArgumentException('Missing `name` property');
        }

        $obj->name = $name;

        $obj->children = array_map([$obj, 'parse'], $content['children'] ?? []);

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
