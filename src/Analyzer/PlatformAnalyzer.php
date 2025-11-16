<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Database\DatabaseManager;
use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Analyze platform-specific compatibility (Moodle, WordPress, etc.)
 */
class PlatformAnalyzer
{
    private DatabaseManager $database;
    private Config $config;
    private Logger $logger;

    public function __construct(DatabaseManager $database, Config $config, Logger $logger)
    {
        $this->database = $database;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Analyze files for platform compatibility
     *
     * @param array $files List of files to analyze
     * @param string $platform Platform name (moodle, wordpress, etc.)
     * @param string|null $fromVersion Starting platform version
     * @param string|null $toVersion Target platform version
     * @return array List of issues found
     */
    public function analyze(array $files, string $platform, ?string $fromVersion = null, ?string $toVersion = null): array
    {
        $this->logger->info("Analyzing {$platform} compatibility");

        $issues = [];

        // Get platform deprecated functions
        $deprecatedData = $this->database->getPlatformDeprecated($platform);

        if (empty($deprecatedData)) {
            $this->logger->warning("No deprecated function data found for {$platform}");
            return $issues;
        }

        $deprecatedFunctions = $deprecatedData['functions'] ?? [];

        // Analyze each file
        foreach ($files as $fileInfo) {
            $filePath = $fileInfo['path'];
            $fileIssues = $this->analyzeFile($filePath, $deprecatedFunctions, $fromVersion, $toVersion);

            foreach ($fileIssues as $issue) {
                $issue['file'] = $filePath;
                $issue['relative_path'] = $fileInfo['relative_path'] ?? $filePath;
                $issue['platform'] = $platform;
                $issues[] = $issue;
            }
        }

        $this->logger->info("Found " . count($issues) . " {$platform} compatibility issues");

        return $issues;
    }

    /**
     * Analyze a single file
     */
    private function analyzeFile(string $filePath, array $deprecatedFunctions, ?string $fromVersion, ?string $toVersion): array
    {
        $issues = [];

        $content = file_get_contents($filePath);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        foreach ($deprecatedFunctions as $func) {
            // Check if function is relevant to version range
            if ($fromVersion && isset($func['removed_in'])) {
                // If function was removed before fromVersion, skip it
                if (version_compare($func['removed_in'], $fromVersion, '<')) {
                    continue;
                }
            }

            if (!isset($func['regex'])) {
                continue;
            }

            // Search for function usage
            if (preg_match_all('/' . $func['regex'] . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $lineNumber = substr_count($content, "\n", 0, $offset) + 1;

                    $issues[] = [
                        'type' => 'deprecated_platform_function',
                        'category' => 'platform_compatibility',
                        'severity' => $func['severity'] ?? 'high',
                        'title' => "Deprecated function: {$func['function']}",
                        'description' => "Function {$func['function']} is deprecated since version {$func['deprecated_since']}" .
                            (isset($func['removed_in']) ? " and removed in {$func['removed_in']}" : ""),
                        'line' => $lineNumber,
                        'code' => $lines[$lineNumber - 1] ?? '',
                        'snippet' => Helpers::extractCodeSnippet($lines, $lineNumber, 2),
                        'replacement' => $func['replacement'] ?? null,
                        'example_old' => $func['example_old'] ?? null,
                        'example_new' => $func['example_new'] ?? null,
                        'deprecated_since' => $func['deprecated_since'] ?? null,
                        'removed_in' => $func['removed_in'] ?? null,
                    ];
                }
            }
        }

        return $issues;
    }
}
