<?php

declare(strict_types=1);

namespace Solido\Atlante\Storage;

/**
 * Represents a storage for authentication tokens.
 */
interface StorageInterface
{
    /**
     * Retrieves an item from the storage.
     */
    public function getItem(string $key): ItemInterface;

    /**
     * Checks if an item is present on the storage.
     */
    public function hasItem(string $key): bool;

    /**
     * Clears the storage. Returns true on success.
     */
    public function clear(): bool;

    /**
     * Deletes an item from the storage.
     * Returns true on success, false otherwise (ex: the item is not present).
     */
    public function deleteItem(string $key): bool;

    /**
     * Saves an item on the storage.
     */
    public function save(ItemInterface $item): bool;
}
