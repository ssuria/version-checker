<?php

namespace PhpMigrationAnalyzer\ServerConfig;

/**
 * Server configuration converter utility
 */
class ConfigConverter
{
    private ApacheParser $apacheParser;
    private NginxGenerator $nginxGenerator;
    private FrankenPHPGenerator $frankenphpGenerator;

    public function __construct()
    {
        $this->apacheParser = new ApacheParser();
        $this->nginxGenerator = new NginxGenerator();
        $this->frankenphpGenerator = new FrankenPHPGenerator();
    }

    /**
     * Convert configuration between server types
     *
     * @param string $content Configuration content
     * @param string $from Source server type
     * @param string $to Target server type
     * @return array Conversion result
     */
    public function convert(string $content, string $from, string $to): array
    {
        $result = [
            'success' => false,
            'from' => $from,
            'to' => $to,
            'converted' => '',
            'warnings' => [],
            'errors' => [],
        ];

        try {
            if (strpos($from, 'apache') === 0) {
                $parsed = $this->apacheParser->parse($content);
                $result['warnings'] = $this->apacheParser->getWarnings();

                if ($to === 'nginx') {
                    $result['converted'] = $this->nginxGenerator->generate($parsed);
                    $result['success'] = true;
                } elseif ($to === 'frankenphp') {
                    $result['converted'] = $this->frankenphpGenerator->generate($parsed);
                    $result['success'] = true;
                } elseif ($to === 'apache24' && $from === 'apache22') {
                    // Upgrade within Apache
                    $result['converted'] = $this->upgradeApache22to24($content);
                    $result['success'] = true;
                } else {
                    $result['errors'][] = "Unsupported conversion: {$from} -> {$to}";
                }
            } else {
                $result['errors'][] = "Unsupported source server: {$from}";
            }

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Upgrade Apache 2.2 to 2.4
     */
    private function upgradeApache22to24(string $content): string
    {
        $lines = explode("\n", $content);
        $converted = [];
        $skipNext = false;

        foreach ($lines as $i => $line) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            $trimmed = trim($line);

            // Convert Order/Allow/Deny blocks
            if (preg_match('/^Order\s+/i', $trimmed)) {
                // Look ahead for Allow/Deny
                $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';

                if (preg_match('/^Allow\s+from\s+all/i', $nextLine)) {
                    $converted[] = '    Require all granted';
                    $skipNext = true;
                    continue;
                } elseif (preg_match('/^Deny\s+from\s+all/i', $nextLine)) {
                    $converted[] = '    Require all denied';
                    $skipNext = true;
                    continue;
                }
            }

            // Single Allow/Deny directives
            if (preg_match('/^Allow\s+from\s+all/i', $trimmed)) {
                $converted[] = str_replace($trimmed, 'Require all granted', $line);
                continue;
            }

            if (preg_match('/^Deny\s+from\s+all/i', $trimmed)) {
                $converted[] = str_replace($trimmed, 'Require all denied', $line);
                continue;
            }

            $converted[] = $line;
        }

        return implode("\n", $converted);
    }
}
