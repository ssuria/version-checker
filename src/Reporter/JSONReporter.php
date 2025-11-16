<?php

namespace PhpMigrationAnalyzer\Reporter;

use PhpMigrationAnalyzer\Core\Config;

/**
 * JSON report generator
 */
class JSONReporter implements ReporterInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generate JSON report
     */
    public function generate(array $results): string
    {
        return json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return 'json';
    }

    /**
     * Get MIME type
     */
    public function getMimeType(): string
    {
        return 'application/json';
    }
}
