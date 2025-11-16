<?php

namespace PhpMigrationAnalyzer\Reporter;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * HTML report generator with professional styling
 */
class HTMLReporter implements ReporterInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generate HTML report
     */
    public function generate(array $results): string
    {
        $html = $this->getHeader($results);
        $html .= $this->getStyles();
        $html .= '</head><body>';
        $html .= $this->getReportHeader($results);
        $html .= $this->getSummarySection($results);
        $html .= $this->getIssuesSection($results);
        $html .= $this->getPluginsSection($results);
        $html .= $this->getServerConfigSection($results);
        $html .= $this->getRoadmapSection($results);
        $html .= $this->getFooter();
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Get HTML header
     */
    private function getHeader(array $results): string
    {
        $title = Helpers::escapeHtml("Migration Analysis - {$results['project_name']}");

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
HTML;
    }

    /**
     * Get CSS styles
     */
    private function getStyles(): string
    {
        return <<<'CSS'
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-critical: #dc3545;
            --color-high: #fd7e14;
            --color-medium: #ffc107;
            --color-low: #28a745;
            --color-info: #17a2b8;
            --color-bg: #f8f9fa;
            --color-card: #ffffff;
            --color-border: #dee2e6;
            --color-text: #212529;
            --color-text-muted: #6c757d;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-lg: 0 4px 12px rgba(0,0,0,0.15);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header .meta {
            opacity: 0.9;
            font-size: 0.95em;
        }

        .card {
            background: var(--color-card);
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-border);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            background: var(--color-bg);
            border-radius: 8px;
        }

        .summary-item .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }

        .summary-item .label {
            color: var(--color-text-muted);
            font-size: 0.9em;
            margin-top: 5px;
        }

        .severity-badges {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            color: white;
        }

        .badge-critical { background: var(--color-critical); }
        .badge-high { background: var(--color-high); }
        .badge-medium { background: var(--color-medium); color: #000; }
        .badge-low { background: var(--color-low); }
        .badge-info { background: var(--color-info); }

        .badge .count {
            margin-left: 8px;
            background: rgba(255,255,255,0.3);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
        }

        .issue-group {
            margin-bottom: 30px;
        }

        .issue-group h3 {
            background: var(--color-bg);
            padding: 12px 15px;
            border-left: 4px solid #667eea;
            font-size: 1.1em;
            margin-bottom: 15px;
        }

        .issue {
            border-left: 4px solid var(--color-border);
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .issue.critical { border-left-color: var(--color-critical); }
        .issue.high { border-left-color: var(--color-high); }
        .issue.medium { border-left-color: var(--color-medium); }
        .issue.low { border-left-color: var(--color-low); }

        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .issue-title {
            font-weight: 600;
            font-size: 1.05em;
        }

        .issue-line {
            color: var(--color-text-muted);
            font-size: 0.9em;
        }

        .issue-description {
            margin: 10px 0;
            color: var(--color-text-muted);
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .code-block .line-number {
            color: #888;
            margin-right: 15px;
            user-select: none;
        }

        .replacement {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .replacement strong {
            display: block;
            margin-bottom: 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .table th {
            background: var(--color-bg);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--color-border);
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid var(--color-border);
        }

        .table tr:hover {
            background: var(--color-bg);
        }

        .migration-path {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: var(--color-bg);
            border-radius: 8px;
        }

        .migration-path .version {
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .migration-path .arrow {
            font-size: 2em;
            color: #667eea;
        }

        .roadmap {
            margin-top: 20px;
        }

        .roadmap-step {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .roadmap-step .number {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .roadmap-step .content {
            flex: 1;
            padding: 15px;
            background: var(--color-bg);
            border-radius: 8px;
        }

        .roadmap-step h4 {
            margin-bottom: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: var(--color-text-muted);
            font-size: 0.9em;
        }

        @media print {
            body {
                background: white;
            }

            .header {
                background: #667eea;
                print-color-adjust: exact;
            }

            .card {
                break-inside: avoid;
            }
        }
    </style>
CSS;
    }

    /**
     * Get report header section
     */
    private function getReportHeader(array $results): string
    {
        $projectName = Helpers::escapeHtml($results['project_name']);
        $scanId = Helpers::escapeHtml($results['scan_id']);
        $timestamp = Helpers::escapeHtml($results['timestamp']);
        $duration = Helpers::escapeHtml($results['duration']);

        return <<<HTML
    <div class="container">
        <div class="header">
            <h1>🔍 Migration Analysis Report</h1>
            <div class="meta">
                <strong>Project:</strong> {$projectName} |
                <strong>Scan ID:</strong> {$scanId} |
                <strong>Date:</strong> {$timestamp} |
                <strong>Duration:</strong> {$duration}s
            </div>
        </div>
HTML;
    }

    /**
     * Get summary section
     */
    private function getSummarySection(array $results): string
    {
        $html = '<div class="card"><h2>📊 Summary</h2>';

        // Migration path
        $html .= '<div class="migration-path">';
        $html .= '<div class="version">';
        $html .= Helpers::escapeHtml($results['platform']) . ' ' . Helpers::escapeHtml($results['current_platform_version']);
        $html .= '<br><small>PHP ' . Helpers::escapeHtml($results['current_php_version']) . '</small>';
        $html .= '</div>';
        $html .= '<div class="arrow">→</div>';
        $html .= '<div class="version">';
        $html .= Helpers::escapeHtml($results['platform']) . ' ' . Helpers::escapeHtml($results['target_platform_version']);
        $html .= '<br><small>PHP ' . Helpers::escapeHtml($results['target_php_version']) . '</small>';
        $html .= '</div>';
        $html .= '</div>';

        // Summary grid
        $html .= '<div class="summary-grid">';
        $html .= '<div class="summary-item">';
        $html .= '<div class="number">' . number_format($results['files']['total']) . '</div>';
        $html .= '<div class="label">Files Analyzed</div>';
        $html .= '</div>';
        $html .= '<div class="summary-item">';
        $html .= '<div class="number">' . number_format($results['summary']['total_issues']) . '</div>';
        $html .= '<div class="label">Issues Found</div>';
        $html .= '</div>';
        if ($results['effort_estimation'] > 0) {
            $html .= '<div class="summary-item">';
            $html .= '<div class="number">' . $results['effort_estimation'] . 'h</div>';
            $html .= '<div class="label">Estimated Effort</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // Severity badges
        $html .= '<div class="severity-badges">';
        foreach ($results['summary']['by_severity'] as $severity => $count) {
            if ($count > 0) {
                $html .= '<div class="badge badge-' . $severity . '">';
                $html .= ucfirst($severity);
                $html .= '<span class="count">' . $count . '</span>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Get issues section
     */
    private function getIssuesSection(array $results): string
    {
        if (empty($results['issues'])) {
            return '';
        }

        $html = '<div class="card"><h2>⚠️ Issues Found</h2>';

        // Group issues by file
        $issuesByFile = [];
        foreach ($results['issues'] as $issue) {
            $file = $issue['relative_path'] ?? $issue['file'];
            if (!isset($issuesByFile[$file])) {
                $issuesByFile[$file] = [];
            }
            $issuesByFile[$file][] = $issue;
        }

        foreach ($issuesByFile as $file => $issues) {
            $fileEscaped = Helpers::escapeHtml($file);
            $html .= "<div class='issue-group'>";
            $html .= "<h3>📄 {$fileEscaped}</h3>";

            foreach ($issues as $issue) {
                $severity = $issue['severity'] ?? 'info';
                $html .= "<div class='issue {$severity}'>";

                $html .= "<div class='issue-header'>";
                $html .= "<div class='issue-title'>" . Helpers::escapeHtml($issue['title']) . "</div>";
                $html .= "<div class='issue-line'>Line " . $issue['line'] . "</div>";
                $html .= "</div>";

                $html .= "<div class='issue-description'>" . Helpers::escapeHtml($issue['description']) . "</div>";

                if (!empty($issue['code'])) {
                    $html .= "<div class='code-block'>";
                    $html .= "<span class='line-number'>" . $issue['line'] . "</span>";
                    $html .= Helpers::escapeHtml($issue['code']);
                    $html .= "</div>";
                }

                if (!empty($issue['replacement'])) {
                    $html .= "<div class='replacement'>";
                    $html .= "<strong>💡 Recommended Fix:</strong> ";
                    $html .= Helpers::escapeHtml($issue['replacement']);
                    $html .= "</div>";
                }

                if (!empty($issue['example_new'])) {
                    $html .= "<div class='code-block'>";
                    $html .= Helpers::escapeHtml($issue['example_new']);
                    $html .= "</div>";
                }

                $html .= "</div>";
            }

            $html .= "</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get plugins section
     */
    private function getPluginsSection(array $results): string
    {
        if (empty($results['plugins'])) {
            return '';
        }

        $html = '<div class="card"><h2>🔌 Custom Components Detected</h2>';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= '<th>Name</th><th>Type</th><th>Version</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($results['plugins'] as $plugin) {
            $html .= '<tr>';
            $html .= '<td>' . Helpers::escapeHtml($plugin['full_name']) . '</td>';
            $html .= '<td>' . Helpers::escapeHtml($plugin['type']) . '</td>';
            $html .= '<td>' . Helpers::escapeHtml($plugin['version']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Get server config section
     */
    private function getServerConfigSection(array $results): string
    {
        if (empty($results['server_config']) || !$results['server_config']['success']) {
            return '';
        }

        $config = $results['server_config'];
        $html = '<div class="card"><h2>⚙️ Server Configuration</h2>';

        $html .= '<p><strong>Conversion:</strong> ' . Helpers::escapeHtml($config['from']) . ' → ' . Helpers::escapeHtml($config['to']) . '</p>';

        if (!empty($config['warnings'])) {
            $html .= '<h3>Warnings</h3><ul>';
            foreach ($config['warnings'] as $warning) {
                $html .= '<li>Line ' . $warning['line'] . ': ' . Helpers::escapeHtml($warning['message']) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($config['converted_content'])) {
            $html .= '<h3>Converted Configuration</h3>';
            $html .= '<div class="code-block">' . Helpers::escapeHtml($config['converted_content']) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get roadmap section
     */
    private function getRoadmapSection(array $results): string
    {
        $html = '<div class="card"><h2>🗺️ Migration Roadmap</h2>';
        $html .= '<div class="roadmap">';

        $steps = [
            ['title' => 'Backup Everything', 'description' => 'Create complete backup of code, database, and files'],
            ['title' => 'Fix Critical Issues', 'description' => 'Address all critical severity issues found in this report'],
            ['title' => 'Update Dependencies', 'description' => 'Update composer packages and platform plugins to compatible versions'],
            ['title' => 'Test on Staging', 'description' => 'Deploy to staging environment and run comprehensive tests'],
            ['title' => 'Update PHP Version', 'description' => 'Upgrade PHP to target version on staging'],
            ['title' => 'Final Testing', 'description' => 'Complete all functionality, security, and performance tests'],
            ['title' => 'Production Deployment', 'description' => 'Deploy to production with rollback plan ready'],
        ];

        $i = 1;
        foreach ($steps as $step) {
            $html .= '<div class="roadmap-step">';
            $html .= '<div class="number">' . $i . '</div>';
            $html .= '<div class="content">';
            $html .= '<h4>' . Helpers::escapeHtml($step['title']) . '</h4>';
            $html .= '<p>' . Helpers::escapeHtml($step['description']) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $i++;
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Get footer
     */
    private function getFooter(): string
    {
        return <<<HTML
        <div class="footer">
            Generated by PHP Migration Analyzer
        </div>
    </div>
HTML;
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return 'html';
    }

    /**
     * Get MIME type
     */
    public function getMimeType(): string
    {
        return 'text/html';
    }
}
