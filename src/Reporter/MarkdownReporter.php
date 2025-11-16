<?php

namespace PhpMigrationAnalyzer\Reporter;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Markdown report generator
 */
class MarkdownReporter implements ReporterInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generate markdown report
     */
    public function generate(array $results): string
    {
        $output = [];

        $output[] = "# PHP Migration Analysis Report";
        $output[] = "";

        // Metadata
        $output[] = "## Project Information";
        $output[] = "";
        $output[] = "| Field | Value |";
        $output[] = "|-------|-------|";
        $output[] = "| Project | {$results['project_name']} |";
        $output[] = "| Scan ID | `{$results['scan_id']}` |";
        $output[] = "| Date | {$results['timestamp']} |";
        $output[] = "| Duration | {$results['duration']}s |";
        $output[] = "";

        // Migration details
        $output[] = "## Migration Details";
        $output[] = "";
        $output[] = "| Component | Current | Target |";
        $output[] = "|-----------|---------|--------|";
        $output[] = "| Platform | {$results['current_platform_version']} | {$results['target_platform_version']} |";
        $output[] = "| PHP | {$results['current_php_version']} | {$results['target_php_version']} |";
        $output[] = "";

        // Summary
        $output[] = "## Summary";
        $output[] = "";
        $output[] = "- **Files Analyzed:** {$results['files']['total']}";
        $output[] = "- **Total Issues:** {$results['summary']['total_issues']}";
        $output[] = "";

        $output[] = "### Issues by Severity";
        $output[] = "";
        foreach ($results['summary']['by_severity'] as $severity => $count) {
            $badge = $this->getSeverityBadge($severity);
            $output[] = "- {$badge} **" . ucfirst($severity) . ":** {$count}";
        }
        $output[] = "";

        if ($results['effort_estimation'] > 0) {
            $output[] = "### Estimated Migration Effort";
            $output[] = "";
            $output[] = "⏱️ **{$results['effort_estimation']} hours**";
            $output[] = "";
        }

        // Issues
        if (!empty($results['issues'])) {
            $output[] = "## Issues Found";
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
                $output[] = "### `{$file}`";
                $output[] = "";

                foreach ($issues as $issue) {
                    $badge = $this->getSeverityBadge($issue['severity']);

                    $output[] = "#### {$badge} {$issue['title']}";
                    $output[] = "";
                    $output[] = "**Line:** {$issue['line']}";
                    $output[] = "";
                    $output[] = "{$issue['description']}";
                    $output[] = "";

                    if (!empty($issue['code'])) {
                        $output[] = "**Current Code:**";
                        $output[] = "```php";
                        $output[] = $issue['code'];
                        $output[] = "```";
                        $output[] = "";
                    }

                    if (!empty($issue['replacement'])) {
                        $output[] = "**Replacement:** {$issue['replacement']}";
                        $output[] = "";
                    }

                    if (!empty($issue['example_new'])) {
                        $output[] = "**Example Fix:**";
                        $output[] = "```php";
                        $output[] = $issue['example_new'];
                        $output[] = "```";
                        $output[] = "";
                    }
                }
            }
        }

        // Plugins
        if (!empty($results['plugins'])) {
            $output[] = "## Custom Components Detected";
            $output[] = "";
            $output[] = "| Name | Type | Version |";
            $output[] = "|------|------|---------|";

            foreach ($results['plugins'] as $plugin) {
                $output[] = "| {$plugin['full_name']} | {$plugin['type']} | {$plugin['version']} |";
            }

            $output[] = "";
        }

        // Server config
        if (!empty($results['server_config']) && $results['server_config']['success']) {
            $output[] = "## Server Configuration";
            $output[] = "";
            $output[] = "**Conversion:** {$results['server_config']['from']} → {$results['server_config']['to']}";
            $output[] = "";

            if (!empty($results['server_config']['warnings'])) {
                $output[] = "### Warnings";
                $output[] = "";
                foreach ($results['server_config']['warnings'] as $warning) {
                    $output[] = "- **Line {$warning['line']}:** {$warning['message']}";
                }
                $output[] = "";
            }

            if (!empty($results['server_config']['converted_content'])) {
                $output[] = "### Converted Configuration";
                $output[] = "";
                $output[] = "```";
                $output[] = $results['server_config']['converted_content'];
                $output[] = "```";
                $output[] = "";
            }
        }

        $output[] = "---";
        $output[] = "*Report generated by PHP Migration Analyzer*";

        return implode("\n", $output);
    }

    /**
     * Get severity badge for markdown
     */
    private function getSeverityBadge(string $severity): string
    {
        $badges = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            'info' => '🔵',
        ];

        return $badges[$severity] ?? '⚪';
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return 'md';
    }

    /**
     * Get MIME type
     */
    public function getMimeType(): string
    {
        return 'text/markdown';
    }
}
