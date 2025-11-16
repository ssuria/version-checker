<?php

namespace PhpMigrationAnalyzer\ServerConfig;

/**
 * Generate Nginx configuration from Apache config
 */
class NginxGenerator
{
    /**
     * Generate Nginx configuration
     *
     * @param array $parsed Parsed Apache configuration
     * @return string Nginx configuration
     */
    public function generate(array $parsed): string
    {
        $config = [];

        $config[] = "# Converted from Apache configuration";
        $config[] = "# Manual review required - some directives may need adjustment";
        $config[] = "";

        // Directory index
        foreach ($parsed['directives'] as $directive) {
            if ($directive['type'] === 'DirectoryIndex') {
                $config[] = "index {$directive['value']};";
                break;
            }
        }

        // Access control
        if (!empty($parsed['access_control'])) {
            $config[] = "";
            $config[] = "# Access Control";

            foreach ($parsed['access_control'] as $ac) {
                if ($ac['type'] === 'Require' && $ac['value'] === 'all granted') {
                    $config[] = "# allow all;";
                } elseif ($ac['type'] === 'Require' && $ac['value'] === 'all denied') {
                    $config[] = "deny all;";
                } elseif ($ac['type'] === 'Allow' && $ac['from'] === 'all') {
                    $config[] = "# allow all;";
                } elseif ($ac['type'] === 'Deny' && $ac['from'] === 'all') {
                    $config[] = "deny all;";
                }
            }
        }

        // PHP settings
        if (!empty($parsed['php_settings'])) {
            $config[] = "";
            $config[] = "# PHP Settings (add to php-fpm pool config or php.ini)";

            foreach ($parsed['php_settings'] as $setting) {
                $config[] = "# php_admin_value[{$setting['name']}] = {$setting['value']}";
            }
        }

        // Rewrite rules
        if (!empty($parsed['rewrite_rules'])) {
            $config[] = "";
            $config[] = "# Rewrite Rules";

            foreach ($parsed['rewrite_rules'] as $rule) {
                $nginxRule = $this->convertRewriteRule($rule);
                if ($nginxRule) {
                    $config[] = $nginxRule;
                }
            }
        }

        // Error documents
        if (!empty($parsed['error_documents'])) {
            $config[] = "";
            $config[] = "# Error Documents";

            foreach ($parsed['error_documents'] as $errorDoc) {
                $config[] = "error_page {$errorDoc['code']} {$errorDoc['target']};";
            }
        }

        // Redirects
        if (!empty($parsed['redirects'])) {
            $config[] = "";
            $config[] = "# Redirects";

            foreach ($parsed['redirects'] as $redirect) {
                $code = $redirect['code'] === '301' ? 'permanent' : 'redirect';
                $config[] = "rewrite {$redirect['from']} {$redirect['to']} {$code};";
            }
        }

        return implode("\n", $config);
    }

    /**
     * Convert Apache RewriteRule to Nginx rewrite
     */
    private function convertRewriteRule(array $rule): ?string
    {
        $pattern = $rule['pattern'];
        $target = $rule['target'];
        $flags = $rule['flags'];

        // Convert pattern
        $nginxPattern = $this->convertPattern($pattern);

        // Determine flags
        $nginxFlags = '';
        if (stripos($flags, 'L') !== false) {
            $nginxFlags = 'last';
        } elseif (stripos($flags, 'R=301') !== false || stripos($flags, 'R,301') !== false) {
            $nginxFlags = 'permanent';
        } elseif (stripos($flags, 'R') !== false) {
            $nginxFlags = 'redirect';
        }

        // Simple conversions
        if ($pattern === '^(.*)$' || $pattern === '(.*)') {
            if ($target === 'index.php') {
                return "try_files \$uri \$uri/ /index.php?\$args;";
            } elseif (strpos($target, 'index.php') === 0) {
                return "try_files \$uri \$uri/ /{$target}?\$args;";
            }
        }

        // Common WordPress rule
        if ($pattern === '^index\\.php$' && $target === '-') {
            return "# Skip if accessing index.php directly";
        }

        // Default conversion
        if ($nginxFlags) {
            return "rewrite {$nginxPattern} {$target} {$nginxFlags};";
        }

        return "rewrite {$nginxPattern} {$target};";
    }

    /**
     * Convert Apache pattern to Nginx pattern
     */
    private function convertPattern(string $pattern): string
    {
        // Remove leading ^
        $pattern = ltrim($pattern, '^');

        // Remove trailing $
        $pattern = rtrim($pattern, '$');

        // Convert common patterns
        $pattern = str_replace('\\.', '\\.', $pattern);

        // Add Nginx pattern markers
        return '^' . $pattern;
    }
}
