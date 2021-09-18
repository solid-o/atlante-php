<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

final class BadResponsePropertyTree
{
    private string $name;

    /** @var string[] */
    private array $errors;

    /** @var static[] */
    private array $children;

    /**
     * @param string[] $errors
     * @param self[] $children
     */
    public function __construct(string $name, array $errors, array $children)
    {
        $this->name = $name;
        $this->errors = $errors;
        $this->children = $children;
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
}
