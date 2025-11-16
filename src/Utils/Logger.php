<?php

namespace PhpMigrationAnalyzer\Utils;

use PhpMigrationAnalyzer\Core\Config;

/**
 * Simple logger
 */
class Logger
{
    private Config $config;
    private ?string $logFile = null;
    private bool $enabled;
    private string $level;

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    ];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->enabled = $config->get('logging.enabled', true);
        $this->level = $config->get('logging.level', 'info');

        if ($this->enabled) {
            $logDir = $config->get('paths.logs');
            $logFileName = $config->get('logging.file', 'analyzer.log');
            $this->logFile = $logDir . '/' . $logFileName;

            // Ensure log directory exists
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a message
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        if ($this->logFile) {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }

        // Also output to STDERR in debug mode
        if ($this->config->get('app.debug', false)) {
            fwrite(STDERR, $logMessage);
        }
    }

    /**
     * Check if message should be logged based on level
     */
    private function shouldLog(string $level): bool
    {
        $currentLevel = self::LEVELS[$this->level] ?? 1;
        $messageLevel = self::LEVELS[$level] ?? 1;

        return $messageLevel >= $currentLevel;
    }
}
