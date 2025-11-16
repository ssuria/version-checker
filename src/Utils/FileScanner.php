<?php

namespace PhpMigrationAnalyzer\Utils;

use PhpMigrationAnalyzer\Core\Config;

/**
 * File scanner utility
 */
class FileScanner
{
    private Config $config;
    private array $excludePatterns = [];
    private array $fileExtensions = [];
    private int $maxFileSize;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->excludePatterns = $config->get('analysis.exclude_patterns', []);
        $this->fileExtensions = $config->get('analysis.file_extensions', ['php']);
        $this->maxFileSize = $config->get('analysis.max_file_size', 5242880);
    }

    /**
     * Scan directory for PHP files
     *
     * @param string $path Base path to scan
     * @param array $additionalPaths Additional paths to scan
     * @param array $customExcludes Custom exclude patterns
     * @return array
     */
    public function scan(string $path, array $additionalPaths = [], array $customExcludes = []): array
    {
        $files = [];
        $excludes = array_merge($this->excludePatterns, $customExcludes);

        // Scan main path
        $files = array_merge($files, $this->scanDirectory($path, $excludes));

        // Scan additional paths
        foreach ($additionalPaths as $additionalPath) {
            $fullPath = $path . '/' . trim($additionalPath, '/');
            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($fullPath, $excludes));
            }
        }

        return $files;
    }

    /**
     * Scan a single directory recursively
     */
    private function scanDirectory(string $directory, array $excludes = []): array
    {
        $files = [];
        $directory = rtrim($directory, '/');

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();

            // Check if file should be excluded
            if ($this->shouldExclude($filePath, $excludes)) {
                continue;
            }

            // Check file extension
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, $this->fileExtensions)) {
                continue;
            }

            // Check file size
            if ($file->getSize() > $this->maxFileSize) {
                continue;
            }

            $files[] = [
                'path' => $filePath,
                'relative_path' => str_replace($directory . '/', '', $filePath),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'hash' => md5_file($filePath),
            ];
        }

        return $files;
    }

    /**
     * Check if file should be excluded
     */
    private function shouldExclude(string $filePath, array $excludes): bool
    {
        foreach ($excludes as $pattern) {
            // Convert glob pattern to regex
            $regex = $this->globToRegex($pattern);
            if (preg_match($regex, $filePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert glob pattern to regex
     */
    private function globToRegex(string $pattern): string
    {
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('?', '.', $pattern);

        return '/^' . $pattern . '$/i';
    }

    /**
     * Get file content
     */
    public function getFileContent(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        return file_get_contents($filePath);
    }

    /**
     * Get file lines
     */
    public function getFileLines(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        return file($filePath, FILE_IGNORE_NEW_LINES);
    }
}
