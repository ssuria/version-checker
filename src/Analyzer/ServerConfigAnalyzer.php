<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Database\DatabaseManager;
use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\ServerConfig\ApacheParser;
use PhpMigrationAnalyzer\ServerConfig\NginxGenerator;
use PhpMigrationAnalyzer\ServerConfig\FrankenPHPGenerator;

/**
 * Analyze and convert server configurations
 */
class ServerConfigAnalyzer
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
     * Analyze server configuration file
     *
     * @param string $configPath Path to configuration file
     * @param string $fromServer Source server type
     * @param string $toServer Target server type
     * @return array Analysis results and converted configuration
     */
    public function analyze(string $configPath, string $fromServer, string $toServer): array
    {
        $this->logger->info("Analyzing server config: {$fromServer} -> {$toServer}");

        if (!file_exists($configPath)) {
            $this->logger->error("Configuration file not found: {$configPath}");
            return [
                'success' => false,
                'error' => 'Configuration file not found',
            ];
        }

        $content = file_get_contents($configPath);

        $result = [
            'success' => true,
            'from' => $fromServer,
            'to' => $toServer,
            'original_file' => $configPath,
            'original_content' => $content,
            'converted_content' => '',
            'issues' => [],
            'warnings' => [],
        ];

        try {
            // Parse source configuration
            if (strpos($fromServer, 'apache') === 0) {
                $parser = new ApacheParser();
                $parsed = $parser->parse($content);
                $result['parsed'] = $parsed;
                $result['issues'] = $parser->getIssues();
                $result['warnings'] = $parser->getWarnings();

                // Generate target configuration
                if ($toServer === 'nginx') {
                    $generator = new NginxGenerator();
                    $result['converted_content'] = $generator->generate($parsed);
                } elseif ($toServer === 'frankenphp') {
                    $generator = new FrankenPHPGenerator();
                    $result['converted_content'] = $generator->generate($parsed);
                } elseif ($toServer === 'apache24') {
                    // Apache 2.2 to 2.4 upgrade
                    $result['converted_content'] = $this->upgradeApache22to24($content);
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("Server config analysis failed: " . $e->getMessage());
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Upgrade Apache 2.2 config to 2.4
     */
    private function upgradeApache22to24(string $content): string
    {
        $lines = explode("\n", $content);
        $converted = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Convert Order/Allow/Deny to Require
            if (preg_match('/^Order\s+/i', $trimmed)) {
                $converted[] = '# ' . $line . ' (converted to Require below)';
                continue;
            }

            if (preg_match('/^Allow\s+from\s+all/i', $trimmed)) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $converted[] = $indent . 'Require all granted';
                continue;
            }

            if (preg_match('/^Deny\s+from\s+all/i', $trimmed)) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $converted[] = $indent . 'Require all denied';
                continue;
            }

            if (preg_match('/^Allow\s+from\s+(.+)$/i', $trimmed, $matches)) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $from = trim($matches[1]);
                $converted[] = $indent . "Require host {$from}";
                continue;
            }

            if (preg_match('/^Deny\s+from\s+(.+)$/i', $trimmed, $matches)) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $from = trim($matches[1]);
                $converted[] = $indent . "Require not host {$from}";
                continue;
            }

            $converted[] = $line;
        }

        return implode("\n", $converted);
    }
}
