<?php

declare(strict_types=1);

namespace Solido\Atlante\Requester\Response;

final class BadResponsePropertyTree
{
    /**
     * @param string[] $errors
     * @param self[] $children
     */
    public function __construct(private string $name, private array $errors, private array $children)
    {
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
