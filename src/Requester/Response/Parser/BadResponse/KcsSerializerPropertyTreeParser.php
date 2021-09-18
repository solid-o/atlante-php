<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response\Parser\BadResponse;

use InvalidArgumentException;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use stdClass;

use function array_map;
use function get_debug_type;
use function is_array;
use function is_string;
use function Safe\sprintf;

class KcsSerializerPropertyTreeParser implements BadResponsePropertyTreeParserInterface
{
    /**
     * @inheritDoc
     */
    public function supports($data): bool
    {
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }

        return is_array($data) &&
            is_array($data['errors'] ?? null) &&
            is_string($data['name'] ?? null) &&
            is_array($data['children'] ?? []);
    }

    /**
     * @inheritDoc
     */
    public function parse($data): BadResponsePropertyTree
    {
        if (is_string($data)) {
            throw new InvalidArgumentException('Unexpected response type, object or array expected, string given');
        }

        $data = (array) $data;

        $errors = $data['errors'] ?? null;
        if ($errors === null) {
            throw new InvalidArgumentException('Unable to parse missing `errors` property');
        }

        if (! is_array($errors)) {
            throw new InvalidArgumentException(sprintf('Invalid `errors` property type, expected array, %s given', get_debug_type($errors)));
        }

        $name = $data['name'] ?? null;
        if ($name === null) {
            throw new InvalidArgumentException('Missing `name` property');
        }

        if (! is_string($name)) {
            throw new InvalidArgumentException(sprintf('Invalid `name` property type, expected string, %s given', get_debug_type($name)));
        }

        $children = $data['children'] ?? [];
        if (! is_array($children)) {
            throw new InvalidArgumentException(sprintf('Invalid `children` property type, expected array, %s given', get_debug_type($children)));
        }

        return new BadResponsePropertyTree($name, $errors, array_map([$this, 'parse'], $children));
    }
}
