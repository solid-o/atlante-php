<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response\Parser\BadResponse;

use InvalidArgumentException;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use stdClass;

use function array_filter;
use function array_map;
use function count;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

class JMSSerializerPropertyTreeParser implements BadResponsePropertyTreeParserInterface
{
    public function supports(mixed $data): bool
    {
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }

        return is_array($data) &&
            (isset($data['errors']) || isset($data['children'])) &&
            is_array($data['errors'] ?? []) &&
            (is_array($data['children'] ?? []) || $data['children'] instanceof stdClass) &&
            count(array_filter(array_map([$this, 'supports'], (array) ($data['children'] ?? [])), static fn ($v) => $v !== true)) === 0;
    }

    public function parse(mixed $data, string $name = ''): BadResponsePropertyTree
    {
        if (is_string($data)) {
            throw new InvalidArgumentException('Unexpected response type, object or array expected, string given');
        }

        $data = (array) $data;

        if (! isset($data['errors']) && ! isset($data['children'])) {
            throw new InvalidArgumentException('Invalid data format');
        }

        $errors = $data['errors'] ?? [];
        if (! is_array($errors)) {
            throw new InvalidArgumentException(sprintf('Invalid `errors` property type, expected array, %s given', get_debug_type($errors)));
        }

        $children = $data['children'] ?? [];
        if ($children instanceof stdClass) {
            $children = (array) $children;
        }

        if (! is_array($children)) {
            throw new InvalidArgumentException(sprintf('Invalid `children` property type, expected array, %s given', get_debug_type($children)));
        }

        $childrenErrors = [];
        foreach ($children as $childName => $child) {
            $childrenErrors[] = $this->parse($child, (string) $childName);
        }

        return new BadResponsePropertyTree($name, $errors, $childrenErrors);
    }
}
