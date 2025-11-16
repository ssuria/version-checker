<?php

namespace PhpMigrationAnalyzer\Core;

/**
 * Simple Dependency Injection Container
 */
class Container
{
    private array $services = [];
    private array $instances = [];

    /**
     * Register a service
     */
    public function set(string $name, callable $resolver): void
    {
        $this->services[$name] = $resolver;
    }

    /**
     * Get a service instance
     */
    public function get(string $name)
    {
        if (!isset($this->instances[$name])) {
            if (!isset($this->services[$name])) {
                throw new \Exception("Service {$name} not found in container");
            }
            $this->instances[$name] = call_user_func($this->services[$name], $this);
        }

        return $this->instances[$name];
    }

    /**
     * Check if service exists
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->instances[$name]);
    }

    /**
     * Set a singleton instance directly
     */
    public function instance(string $name, $instance): void
    {
        $this->instances[$name] = $instance;
    }

    /**
     * Remove a service
     */
    public function remove(string $name): void
    {
        unset($this->services[$name]);
        unset($this->instances[$name]);
    }
}
