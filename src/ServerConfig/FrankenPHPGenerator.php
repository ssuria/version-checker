<?php

namespace PhpMigrationAnalyzer\ServerConfig;

/**
 * Generate FrankenPHP (Caddy) configuration from Apache config
 */
class FrankenPHPGenerator
{
    /**
     * Generate FrankenPHP/Caddy configuration
     *
     * @param array $parsed Parsed Apache configuration
     * @return string Caddyfile configuration
     */
    public function generate(array $parsed): string
    {
        $config = [];

        $config[] = "# Converted from Apache configuration for FrankenPHP/Caddy";
        $config[] = "# Manual review required";
        $config[] = "";
        $config[] = "{";
        $config[] = "\t# Global options";
        $config[] = "\tfrankenphp";
        $config[] = "}";
        $config[] = "";
        $config[] = "localhost {";

        // PHP settings
        if (!empty($parsed['php_settings'])) {
            $config[] = "\t# PHP Settings";

            foreach ($parsed['php_settings'] as $setting) {
                $config[] = "\t# {$setting['name']} = {$setting['value']}";
            }

            $config[] = "";
        }

        // Root and file server
        $config[] = "\troot * /var/www/html";
        $config[] = "\tencode gzip";
        $config[] = "";

        // Directory index
        foreach ($parsed['directives'] as $directive) {
            if ($directive['type'] === 'DirectoryIndex') {
                $files = explode(' ', $directive['value']);
                $config[] = "\tfile_server {";
                $config[] = "\t\tindex " . implode(' ', $files);
                $config[] = "\t}";
                break;
            }
        }

        // PHP handler
        $config[] = "";
        $config[] = "\t# PHP handler";
        $config[] = "\tphp_server";
        $config[] = "";

        // Rewrite rules
        if (!empty($parsed['rewrite_rules'])) {
            $config[] = "\t# Rewrite Rules";

            foreach ($parsed['rewrite_rules'] as $rule) {
                $caddyRule = $this->convertRewriteRule($rule);
                if ($caddyRule) {
                    foreach (explode("\n", $caddyRule) as $line) {
                        $config[] = "\t" . $line;
                    }
                }
            }

            $config[] = "";
        }

        // Error documents
        if (!empty($parsed['error_documents'])) {
            $config[] = "\t# Error Pages";
            $config[] = "\thandle_errors {";

            foreach ($parsed['error_documents'] as $errorDoc) {
                $config[] = "\t\t@{$errorDoc['code']} {";
                $config[] = "\t\t\texpression {http.error.status_code} == {$errorDoc['code']}";
                $config[] = "\t\t}";
                $config[] = "\t\trewrite @{$errorDoc['code']} {$errorDoc['target']}";
            }

            $config[] = "\t}";
            $config[] = "";
        }

        // Redirects
        if (!empty($parsed['redirects'])) {
            $config[] = "\t# Redirects";

            foreach ($parsed['redirects'] as $redirect) {
                $permanent = $redirect['code'] === '301' ? 'permanent' : '';
                $config[] = "\tredir {$redirect['from']} {$redirect['to']} {$permanent}";
            }

            $config[] = "";
        }

        // Access control
        if (!empty($parsed['access_control'])) {
            $config[] = "\t# Access Control";

            foreach ($parsed['access_control'] as $ac) {
                if ($ac['type'] === 'Deny' && $ac['from'] === 'all') {
                    $config[] = "\t# Deny all access";
                    $config[] = "\trespond 403";
                }
            }
        }

        $config[] = "}";

        return implode("\n", $config);
    }

    /**
     * Convert Apache RewriteRule to Caddy rule
     */
    private function convertRewriteRule(array $rule): ?string
    {
        $pattern = $rule['pattern'];
        $target = $rule['target'];

        // Common WordPress/PHP patterns
        if ($pattern === '^index\\.php$' && $target === '-') {
            return null; // Skip
        }

        if ($pattern === '^(.*)$' || $pattern === '(.*)') {
            if (strpos($target, 'index.php') !== false) {
                return "try_files {path} {path}/ /index.php?{query}";
            }
        }

        // Default: use rewrite matcher
        $caddyPattern = $this->convertPattern($pattern);

        return "@rewrite {\n" .
               "\tpath_regexp {$caddyPattern}\n" .
               "}\n" .
               "rewrite @rewrite {$target}";
    }

    /**
     * Convert Apache pattern to Caddy pattern
     */
    private function convertPattern(string $pattern): string
    {
        // Basic conversion
        $pattern = ltrim($pattern, '^');
        $pattern = rtrim($pattern, '$');

        return '^' . $pattern . '$';
    }
}
