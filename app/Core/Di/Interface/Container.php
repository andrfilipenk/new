<?php

namespace Core\Di\Interface;

/**
 * PSR-11 Container Interface
 * Describes the interface of a container that exposes methods to read its entries.
 */
interface Container
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @throws \Core\Di\Interface\NotFoundException  No entry was found for **this** identifier.
     * @throws \Core\Di\Interface\ContainerException Error while retrieving the entry.
     * @return mixed Entry.
     */
    public function get(string $id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}