<?php

declare(strict_types=1);

namespace Solido\Atlante\Tests\Requester\Response\Parser\BadResponse;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Solido\Atlante\Requester\Response\BadResponsePropertyTree;
use Solido\Atlante\Requester\Response\Parser\BadResponse\JMSSerializerPropertyTreeParser;
use Throwable;

use function PHPUnit\Framework\assertCount;

class JMSSerializerPropertyTreeTest extends TestCase
{
    /**
     * @param object|array<string,mixed>|string $content
     *
     * @dataProvider provideParseCases
     */
    public function testParse($content): void
    {
        $parser = new JMSSerializerPropertyTreeParser();
        $parsed = $parser->parse($content);
        self::assertInstanceOf(BadResponsePropertyTree::class, $parsed);
        self::assertSame('', $parsed->getName());
        self::assertEmpty($parsed->getErrors());
        $children = $parsed->getChildren();
        self::assertCount(2, $children);
        foreach ($children as $child) {
            self::assertInstanceOf(BadResponsePropertyTree::class, $child);
        }

        self::assertSame('foo', $children[0]->getName());
        self::assertSame(['Required.'], $children[0]->getErrors());
        self::assertEmpty($children[0]->getChildren());

        self::assertSame('bar', $children[1]->getName());
        self::assertEmpty($children[1]->getErrors());
        $subchildren = $children[1]->getChildren();
        assertCount(1, $subchildren);
        foreach ($subchildren as $child) {
            self::assertInstanceOf(BadResponsePropertyTree::class, $child);
        }

        self::assertSame('baz', $subchildren[0]->getName());
        self::assertSame(['Bazbar'], $subchildren[0]->getErrors());
        self::assertEmpty($subchildren[0]->getChildren());
    }

    public static function provideParseCases(): Generator
    {
        yield [
            [
                'children' => [
                    'foo' => [
                        'errors' => ['Required.'],
                    ],
                    'bar' => [
                        'children' => [
                            'baz' => [
                                'errors' => ['Bazbar'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield [
            (object) [
                'children' => [
                    'foo' => (object) [
                        'errors' => ['Required.'],
                    ],
                    'bar' => (object) [
                        'children' => (object) [
                            'baz' => (object) [
                                'errors' => ['Bazbar'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param object|array<string,mixed>|string $content
     * @phpstan-param class-string<Throwable> $exceptionClass
     *
     * @dataProvider provideBadCases
     */
    public function testBadCases($content, string $exceptionClass, ?string $message = null): void
    {
        $this->expectException($exceptionClass);
        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        $parser = new JMSSerializerPropertyTreeParser();
        $parser->parse($content);
    }

    /**
     * @param object|array<string,mixed>|string $content
     *
     * @dataProvider provideParseCases
     */
    public function testSupports($content): void
    {
        $parser = new JMSSerializerPropertyTreeParser();
        self::assertTrue($parser->supports($content));
    }

    /**
     * @param object|array<string,mixed>|string $content
     *
     * @dataProvider provideBadCases
     */
    public function testSupportsOnBadCases($content): void
    {
        $parser = new JMSSerializerPropertyTreeParser();
        self::assertFalse($parser->supports($content));
    }

    public static function provideBadCases(): Generator
    {
        yield ['foobar', InvalidArgumentException::class, 'Unexpected response type, object or array expected, string given'];
        yield [['name' => 'foobar'], InvalidArgumentException::class, 'Invalid data format'];
        yield [['errors' => 'foo'], InvalidArgumentException::class, 'Invalid `errors` property type, expected array, string given'];
        yield [['errors' => [], 'children' => 'foobar'], InvalidArgumentException::class, 'Invalid `children` property type, expected array, string given'];
        yield [['errors' => [], 'children' => ['foobar']], InvalidArgumentException::class, 'Unexpected response type, object or array expected, string given'];
    }
}
