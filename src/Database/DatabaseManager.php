<?php

namespace PhpMigrationAnalyzer\Database;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Database manager for loading JSON data files
 */
class DatabaseManager
{
    private Config $config;
    private string $databasePath;
    private array $cache = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->databasePath = $config->get('paths.database');
    }

    /**
     * Get PHP version changes
     */
    public function getPhpChanges(string $fromVersion, string $toVersion): ?array
    {
        $cacheKey = "php_{$fromVersion}_to_{$toVersion}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fileName = "{$fromVersion}-to-{$toVersion}.json";
        $filePath = $this->databasePath . '/php-changes/' . $fileName;

        $data = Helpers::readJson($filePath);

        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }

        return $data;
    }

    /**
     * Get all PHP changes between two versions (may span multiple version files)
     */
    public function getAllPhpChanges(string $fromVersion, string $toVersion): array
    {
        $allChanges = [
            'removed_functions' => [],
            'deprecated_features' => [],
            'behavior_changes' => [],
            'new_features' => [],
        ];

        // Define version progression
        $versions = ['7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3'];

        $fromIndex = array_search($fromVersion, $versions);
        $toIndex = array_search($toVersion, $versions);

        if ($fromIndex === false || $toIndex === false || $fromIndex >= $toIndex) {
            return $allChanges;
        }

        // Load changes for each version transition
        for ($i = $fromIndex; $i < $toIndex; $i++) {
            $from = $versions[$i];
            $to = $versions[$i + 1];

            $changes = $this->getPhpChanges($from, $to);

            if ($changes !== null) {
                foreach (['removed_functions', 'deprecated_features', 'behavior_changes', 'new_features'] as $key) {
                    if (isset($changes[$key])) {
                        $allChanges[$key] = array_merge($allChanges[$key], $changes[$key]);
                    }
                }
            }
        }

        return $allChanges;
    }

    /**
     * Get platform deprecated functions
     */
    public function getPlatformDeprecated(string $platform): ?array
    {
        $cacheKey = "platform_{$platform}_deprecated";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filePath = $this->databasePath . "/platforms/{$platform}/deprecated-functions.json";

        $data = Helpers::readJson($filePath);

        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }

        return $data;
    }

    /**
     * Get platform metadata
     */
    public function getPlatformMetadata(string $platform): ?array
    {
        $cacheKey = "platform_{$platform}_metadata";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filePath = $this->databasePath . "/platforms/{$platform}/metadata.json";

        $data = Helpers::readJson($filePath);

        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }

        return $data;
    }

    /**
     * Get platform version changes
     */
    public function getPlatformVersionChanges(string $platform, string $fromVersion, string $toVersion): ?array
    {
        $cacheKey = "platform_{$platform}_{$fromVersion}_to_{$toVersion}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fileName = "{$fromVersion}-to-{$toVersion}.json";
        $filePath = $this->databasePath . "/platforms/{$platform}/versions/{$fileName}";

        $data = Helpers::readJson($filePath);

        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }

        return $data;
    }

    /**
     * Get server config conversion rules
     */
    public function getServerConfigRules(string $from, string $to): ?array
    {
        $cacheKey = "server_{$from}_to_{$to}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fileName = "{$from}-to-{$to}.json";
        $filePath = $this->databasePath . "/server-configs/{$fileName}";

        $data = Helpers::readJson($filePath);

        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }

        return $data;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get all available PHP versions
     */
    public function getAvailablePhpVersions(): array
    {
        return ['7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3'];
    }

    /**
     * Get all available platforms
     */
    public function getAvailablePlatforms(): array
    {
        return ['moodle', 'wordpress', 'magento', 'prestashop', 'symfony', 'laravel', 'generic'];
    }
}
