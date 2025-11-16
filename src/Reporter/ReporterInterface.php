<?php

namespace PhpMigrationAnalyzer\Reporter;

/**
 * Reporter interface for generating reports in different formats
 */
interface ReporterInterface
{
    /**
     * Generate report
     *
     * @param array $results Analysis results
     * @return string Generated report content
     */
    public function generate(array $results): string;

    /**
     * Get report file extension
     *
     * @return string File extension without dot
     */
    public function getExtension(): string;

    /**
     * Get report MIME type
     *
     * @return string MIME type
     */
    public function getMimeType(): string;
}
