<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response\Parser\BadResponse;

use InvalidArgumentException;

class BadResponsePropertyTreeParserFactory
{
    /** @var BadResponsePropertyTreeParserInterface[] */
    private array $parsers;

    /** @param BadResponsePropertyTreeParserInterface[]|null $parsers */
    public function __construct(array|null $parsers = null)
    {
        $this->parsers = $parsers ?? [
            new KcsSerializerPropertyTreeParser(),
            new JMSSerializerPropertyTreeParser(),
        ];
    }

    /**
     * Constructs and returns the first parser capable of parsing the given data.
     * Throws InvalidArgumentException if no parser is found.
     */
    public function factory(mixed $data): BadResponsePropertyTreeParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($data)) {
                return $parser;
            }
        }

        throw new InvalidArgumentException('Unsupported bad response data');
    }
}
