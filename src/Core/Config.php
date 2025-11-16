<?php

namespace PhpMigrationAnalyzer\Core;

/**
 * Configuration manager
 */
class Config
{
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Load configuration from file
     */
    public static function load(string $path): self
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: {$path}");
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new \Exception("Configuration file must return an array");
        }

        return new self($config);
    }

    /**
     * Get configuration value using dot notation
     *
     * @param string $key Key in dot notation (e.g., 'app.name')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set configuration value using dot notation
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }

            $config = &$config[$key];
        }

        $config[array_shift($keys)] = $value;
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Merge configuration
     */
    public function merge(array $config): void
    {
        $this->config = array_replace_recursive($this->config, $config);
    }
}
