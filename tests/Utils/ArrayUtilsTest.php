<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Solido\Atlante\Utils\ArrayUtils;

class ArrayUtilsTest extends TestCase
{
    /**
     * @dataProvider provideDataToCamelCaseKeys
     */
    public function testToCamelCaseKeys(array $expected, array $input): void
    {
        self::assertEquals($expected, ArrayUtils::toCamelCaseKeys($input));
    }

    public function provideDataToCamelCaseKeys(): iterable
    {
        yield [
            ['_id' => '12', 'greatKey' => 'value_not_to_be_converted', 'another' => [['nestedOne' => 1], ['nestedTwo' => 2]]],
            ['_id' => '12', 'great_key' => 'value_not_to_be_converted', 'another' => [['nested_one' => 1], ['nested_two' => 2]]],
        ];
    }

    /**
     * @dataProvider provideDataToSnakeCaseKeys
     */
    public function testToSnakeCaseKeys(array $expected, array $input): void
    {
        self::assertEquals($expected, ArrayUtils::toSnakeCaseKeys($input));
    }

    public function provideDataToSnakeCaseKeys(): iterable
    {
        yield [
            ['_id' => '12', 'great_key' => 'valueNotToBeConverted', 'another' => [['nested_one' => 1], ['nested_two' => 2]]],
            ['_id' => '12', 'greatKey' => 'valueNotToBeConverted', 'another' => [['nestedOne' => 1], ['nestedTwo' => 2]]],
        ];
    }
}
