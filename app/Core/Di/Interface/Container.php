<?php

namespace Core\Di\Interface;

/**
 * Container interface for dependency injection
 */
interface Container
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     */
    public function get(string $id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Register a service definition
     *
     * @param string $id Identifier of the entry
     * @param mixed $concrete The concrete definition
     */
    public function set(string $id, $concrete): void;
}