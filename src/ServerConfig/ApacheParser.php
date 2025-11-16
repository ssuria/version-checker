<?php

namespace PhpMigrationAnalyzer\ServerConfig;

/**
 * Parse Apache .htaccess files
 */
class ApacheParser
{
    private array $issues = [];
    private array $warnings = [];

    /**
     * Parse Apache configuration
     *
     * @param string $content Apache configuration content
     * @return array Parsed configuration
     */
    public function parse(string $content): array
    {
        $this->issues = [];
        $this->warnings = [];

        $parsed = [
            'directives' => [],
            'rewrite_rules' => [],
            'access_control' => [],
            'php_settings' => [],
            'error_documents' => [],
            'redirects' => [],
        ];

        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments
            if (empty($trimmed) || $trimmed[0] === '#') {
                continue;
            }

            // Rewrite rules
            if (preg_match('/^RewriteRule\s+(.+?)\s+(.+?)(?:\s+\[(.+?)\])?$/i', $trimmed, $matches)) {
                $parsed['rewrite_rules'][] = [
                    'pattern' => $matches[1],
                    'target' => $matches[2],
                    'flags' => isset($matches[3]) ? $matches[3] : '',
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // Rewrite conditions
            if (preg_match('/^RewriteCond\s+(.+?)\s+(.+?)(?:\s+\[(.+?)\])?$/i', $trimmed, $matches)) {
                $parsed['directives'][] = [
                    'type' => 'RewriteCond',
                    'test' => $matches[1],
                    'pattern' => $matches[2],
                    'flags' => isset($matches[3]) ? $matches[3] : '',
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // RewriteEngine
            if (preg_match('/^RewriteEngine\s+(On|Off)/i', $trimmed, $matches)) {
                $parsed['directives'][] = [
                    'type' => 'RewriteEngine',
                    'value' => $matches[1],
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // RewriteBase
            if (preg_match('/^RewriteBase\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['directives'][] = [
                    'type' => 'RewriteBase',
                    'value' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // Order directive (Apache 2.2)
            if (preg_match('/^Order\s+(.+)$/i', $trimmed, $matches)) {
                $this->warnings[] = [
                    'line' => $lineNumber + 1,
                    'message' => 'Order directive is deprecated in Apache 2.4+',
                    'directive' => 'Order',
                ];
                $parsed['access_control'][] = [
                    'type' => 'Order',
                    'value' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'apache22' => true,
                ];
                continue;
            }

            // Allow directive (Apache 2.2)
            if (preg_match('/^Allow\s+from\s+(.+)$/i', $trimmed, $matches)) {
                $this->warnings[] = [
                    'line' => $lineNumber + 1,
                    'message' => 'Allow directive is deprecated in Apache 2.4+',
                    'directive' => 'Allow',
                ];
                $parsed['access_control'][] = [
                    'type' => 'Allow',
                    'from' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'apache22' => true,
                ];
                continue;
            }

            // Deny directive (Apache 2.2)
            if (preg_match('/^Deny\s+from\s+(.+)$/i', $trimmed, $matches)) {
                $this->warnings[] = [
                    'line' => $lineNumber + 1,
                    'message' => 'Deny directive is deprecated in Apache 2.4+',
                    'directive' => 'Deny',
                ];
                $parsed['access_control'][] = [
                    'type' => 'Deny',
                    'from' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'apache22' => true,
                ];
                continue;
            }

            // Require directive (Apache 2.4)
            if (preg_match('/^Require\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['access_control'][] = [
                    'type' => 'Require',
                    'value' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'apache24' => true,
                ];
                continue;
            }

            // PHP settings
            if (preg_match('/^php_(?:value|flag)\s+(.+?)\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['php_settings'][] = [
                    'name' => trim($matches[1]),
                    'value' => trim($matches[2]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // Error documents
            if (preg_match('/^ErrorDocument\s+(\d+)\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['error_documents'][] = [
                    'code' => $matches[1],
                    'target' => trim($matches[2]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // Redirect/RedirectMatch
            if (preg_match('/^Redirect(?:Match)?\s+(?:(\d+)\s+)?(.+?)\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['redirects'][] = [
                    'code' => $matches[1] ?? '302',
                    'from' => trim($matches[2]),
                    'to' => trim($matches[3]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // DirectoryIndex
            if (preg_match('/^DirectoryIndex\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['directives'][] = [
                    'type' => 'DirectoryIndex',
                    'value' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }

            // Options
            if (preg_match('/^Options\s+(.+)$/i', $trimmed, $matches)) {
                $parsed['directives'][] = [
                    'type' => 'Options',
                    'value' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                ];
                continue;
            }
        }

        return $parsed;
    }

    /**
     * Get parsing issues
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Get parsing warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
