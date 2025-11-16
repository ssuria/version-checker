<?php

namespace PhpMigrationAnalyzer\Database;

use PhpMigrationAnalyzer\Core\Config;

/**
 * Simple file-based cache manager
 */
class CacheManager
{
    private Config $config;
    private string $cachePath;
    private bool $enabled;
    private int $ttl;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cachePath = $config->get('paths.cache');
        $this->enabled = $config->get('cache.enabled', true);
        $this->ttl = $config->get('cache.ttl', 3600);

        // Ensure cache directory exists
        if ($this->enabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Get value from cache
     */
    public function get(string $key)
    {
        if (!$this->enabled) {
            return null;
        }

        $filePath = $this->getCacheFilePath($key);

        if (!file_exists($filePath)) {
            return null;
        }

        $data = unserialize(file_get_contents($filePath));

        // Check if expired
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set value in cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->ttl;

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];

        $filePath = $this->getCacheFilePath($key);

        return file_put_contents($filePath, serialize($data)) !== false;
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $filePath = $this->getCacheFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpired(): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $count = 0;
        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $data = unserialize(file_get_contents($file));

            if ($data['expires_at'] < time()) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remember: Get from cache or execute callback and cache result
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get cache file path for key
     */
    private function getCacheFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->cachePath . '/' . $hash . '.cache';
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        if (!$this->enabled) {
            return [
                'enabled' => false,
            ];
        }

        $files = glob($this->cachePath . '/*.cache');
        $totalSize = 0;
        $expired = 0;
        $valid = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $totalSize += filesize($file);
            $data = unserialize(file_get_contents($file));

            if ($data['expires_at'] < time()) {
                $expired++;
            } else {
                $valid++;
            }
        }

        return [
            'enabled' => true,
            'total_entries' => count($files),
            'valid_entries' => $valid,
            'expired_entries' => $expired,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Format bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
