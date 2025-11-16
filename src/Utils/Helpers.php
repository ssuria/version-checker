<?php

namespace PhpMigrationAnalyzer\Utils;

/**
 * General helper functions
 */
class Helpers
{
    /**
     * Sanitize path
     */
    public static function sanitizePath(string $path): string
    {
        $path = str_replace(['../', '..\\'], '', $path);
        $path = preg_replace('#/+#', '/', $path);
        return rtrim($path, '/');
    }

    /**
     * Format file size
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Format duration
     */
    public static function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 2) . 's';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . 'm ' . round($seconds) . 's';
        }

        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Truncate string
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Get severity color (for HTML/terminal output)
     */
    public static function getSeverityColor(string $severity): string
    {
        $colors = [
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#28a745',
            'info' => '#17a2b8',
        ];

        return $colors[$severity] ?? '#6c757d';
    }

    /**
     * Get severity label
     */
    public static function getSeverityLabel(string $severity): string
    {
        $labels = [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            'info' => 'Info',
        ];

        return $labels[$severity] ?? 'Unknown';
    }

    /**
     * Pluralize word
     */
    public static function pluralize(int $count, string $singular, string $plural = null): string
    {
        if ($plural === null) {
            $plural = $singular . 's';
        }

        return $count === 1 ? $singular : $plural;
    }

    /**
     * Convert snake_case to camelCase
     */
    public static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Ensure directory exists
     */
    public static function ensureDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }

        return true;
    }

    /**
     * Get relative path
     */
    public static function getRelativePath(string $from, string $to): string
    {
        $from = explode('/', rtrim($from, '/'));
        $to = explode('/', rtrim($to, '/'));

        $relPath = $to;

        foreach ($from as $depth => $dir) {
            if (isset($to[$depth]) && $dir === $to[$depth]) {
                array_shift($relPath);
            } else {
                $remaining = count($from) - $depth;
                if ($remaining > 0) {
                    $padLength = count($relPath) + $remaining;
                    $relPath = array_pad($relPath, -$padLength, '..');
                    break;
                }
            }
        }

        return implode('/', $relPath);
    }

    /**
     * Read JSON file
     */
    public static function readJson(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Write JSON file
     */
    public static function writeJson(string $path, array $data, bool $pretty = true): bool
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($data, $options);

        if ($json === false) {
            return false;
        }

        return file_put_contents($path, $json) !== false;
    }

    /**
     * Extract code snippet around a line
     */
    public static function extractCodeSnippet(array $lines, int $lineNumber, int $context = 2): array
    {
        $start = max(0, $lineNumber - $context - 1);
        $end = min(count($lines) - 1, $lineNumber + $context - 1);

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $snippet[$i + 1] = $lines[$i] ?? '';
        }

        return $snippet;
    }

    /**
     * Escape HTML
     */
    public static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate UUID v4
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
