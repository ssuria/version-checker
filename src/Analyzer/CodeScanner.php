<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\Utils\FileScanner;

/**
 * Code scanner that scans directories for PHP files
 */
class CodeScanner
{
    private Config $config;
    private Logger $logger;
    private FileScanner $fileScanner;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->fileScanner = new FileScanner($config);
    }

    /**
     * Scan directories and return list of files
     *
     * @param string $basePath Base path to scan
     * @param array $additionalPaths Additional paths to scan
     * @param array $excludePatterns Custom exclude patterns
     * @return array
     */
    public function scan(string $basePath, array $additionalPaths = [], array $excludePatterns = []): array
    {
        $this->logger->info("Scanning directory: {$basePath}");

        $startTime = microtime(true);

        $files = $this->fileScanner->scan($basePath, $additionalPaths, $excludePatterns);

        $duration = microtime(true) - $startTime;

        $result = [
            'base_path' => $basePath,
            'additional_paths' => $additionalPaths,
            'files' => $files,
            'total' => count($files),
            'total_size' => array_sum(array_column($files, 'size')),
            'scan_duration' => round($duration, 2),
        ];

        $this->logger->info("Scan completed: {$result['total']} files found in {$duration}s");

        return $result;
    }

    /**
     * Get file statistics
     */
    public function getFileStatistics(array $files): array
    {
        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'by_extension' => [],
            'largest_files' => [],
        ];

        foreach ($files as $file) {
            $stats['total_size'] += $file['size'];

            $ext = pathinfo($file['path'], PATHINFO_EXTENSION);
            if (!isset($stats['by_extension'][$ext])) {
                $stats['by_extension'][$ext] = [
                    'count' => 0,
                    'size' => 0,
                ];
            }

            $stats['by_extension'][$ext]['count']++;
            $stats['by_extension'][$ext]['size'] += $file['size'];
        }

        // Get largest files
        usort($files, function ($a, $b) {
            return $b['size'] - $a['size'];
        });

        $stats['largest_files'] = array_slice($files, 0, 10);

        return $stats;
    }
}
