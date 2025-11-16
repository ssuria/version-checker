<?php

namespace PhpMigrationAnalyzer\Reporter;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Plain text report generator
 */
class TextReporter implements ReporterInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generate text report
     */
    public function generate(array $results): string
    {
        $output = [];

        $output[] = str_repeat('=', 80);
        $output[] = "PHP MIGRATION ANALYSIS REPORT";
        $output[] = str_repeat('=', 80);
        $output[] = "";

        // Metadata
        $output[] = "Project: {$results['project_name']}";
        $output[] = "Scan ID: {$results['scan_id']}";
        $output[] = "Date: {$results['timestamp']}";
        $output[] = "Duration: {$results['duration']}s";
        $output[] = "";

        // Migration details
        $output[] = str_repeat('-', 80);
        $output[] = "MIGRATION DETAILS";
        $output[] = str_repeat('-', 80);
        $output[] = "Platform: {$results['platform']} ({$results['current_platform_version']} → {$results['target_platform_version']})";
        $output[] = "PHP Version: {$results['current_php_version']} → {$results['target_php_version']}";
        $output[] = "";

        // Summary
        $output[] = str_repeat('-', 80);
        $output[] = "SUMMARY";
        $output[] = str_repeat('-', 80);
        $output[] = "Total Files Analyzed: {$results['files']['total']}";
        $output[] = "Total Issues Found: {$results['summary']['total_issues']}";
        $output[] = "";
        $output[] = "Issues by Severity:";
        foreach ($results['summary']['by_severity'] as $severity => $count) {
            $output[] = sprintf("  %-12s: %d", ucfirst($severity), $count);
        }
        $output[] = "";

        if ($results['effort_estimation'] > 0) {
            $output[] = "Estimated Migration Effort: {$results['effort_estimation']} hours";
            $output[] = "";
        }

        // Issues
        if (!empty($results['issues'])) {
            $output[] = str_repeat('-', 80);
            $output[] = "ISSUES FOUND";
            $output[] = str_repeat('-', 80);
            $output[] = "";

            $issuesByFile = [];
            foreach ($results['issues'] as $issue) {
                $file = $issue['relative_path'] ?? $issue['file'];
                if (!isset($issuesByFile[$file])) {
                    $issuesByFile[$file] = [];
                }
                $issuesByFile[$file][] = $issue;
            }

            foreach ($issuesByFile as $file => $issues) {
                $output[] = "File: {$file}";
                $output[] = str_repeat('-', 80);

                foreach ($issues as $issue) {
                    $output[] = "";
                    $output[] = "[{$issue['severity']}] {$issue['title']}";
                    $output[] = "Line: {$issue['line']}";
                    $output[] = "Description: {$issue['description']}";

                    if (!empty($issue['code'])) {
                        $output[] = "Code: {$issue['code']}";
                    }

                    if (!empty($issue['replacement'])) {
                        $output[] = "Replacement: {$issue['replacement']}";
                    }

                    $output[] = "";
                }

                $output[] = "";
            }
        }

        // Plugins
        if (!empty($results['plugins'])) {
            $output[] = str_repeat('-', 80);
            $output[] = "CUSTOM COMPONENTS DETECTED";
            $output[] = str_repeat('-', 80);
            $output[] = "";

            foreach ($results['plugins'] as $plugin) {
                $output[] = "- {$plugin['full_name']} ({$plugin['type']}) - Version: {$plugin['version']}";
            }

            $output[] = "";
        }

        // Server config
        if (!empty($results['server_config']) && $results['server_config']['success']) {
            $output[] = str_repeat('-', 80);
            $output[] = "SERVER CONFIGURATION";
            $output[] = str_repeat('-', 80);
            $output[] = "Conversion: {$results['server_config']['from']} → {$results['server_config']['to']}";
            $output[] = "";

            if (!empty($results['server_config']['warnings'])) {
                $output[] = "Warnings:";
                foreach ($results['server_config']['warnings'] as $warning) {
                    $output[] = "  - Line {$warning['line']}: {$warning['message']}";
                }
                $output[] = "";
            }

            if (!empty($results['server_config']['converted_content'])) {
                $output[] = "Converted Configuration:";
                $output[] = str_repeat('-', 40);
                $output[] = $results['server_config']['converted_content'];
                $output[] = str_repeat('-', 40);
                $output[] = "";
            }
        }

        $output[] = str_repeat('=', 80);
        $output[] = "END OF REPORT";
        $output[] = str_repeat('=', 80);

        return implode("\n", $output);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return 'txt';
    }

    /**
     * Get MIME type
     */
    public function getMimeType(): string
    {
        return 'text/plain';
    }
}
