<?php
namespace TinyNonce;

interface StorageEngine {
    /**
     * Returns a value from the storage engine
     *
     * @param string $name The storage index
     *
     * @returns mixed The stored value
     */
    public function get(string $name);

    /**
     * Sets a value for storage in the engine
     *
     * @param string $name  The storage index
     * @param mixed  $value The value to store in the index
     */
    public function set(string $name, $value);

    /**
     * Checks for the existance of an item
     *
     * @param string $name  The storage index
     *
     * @returns bool Whether or not the value exists
     */
    public function has(string $name);

    /**
     * Deletes the value in the store
     *
     * @param string $name  The storage index
     */
    public function delete(string $name);
}