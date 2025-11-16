<?php

namespace PhpMigrationAnalyzer\Core;

use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\Analyzer\CodeScanner;
use PhpMigrationAnalyzer\Analyzer\PHPVersionAnalyzer;
use PhpMigrationAnalyzer\Analyzer\PlatformAnalyzer;
use PhpMigrationAnalyzer\Analyzer\PluginDetector;
use PhpMigrationAnalyzer\Analyzer\ServerConfigAnalyzer;
use PhpMigrationAnalyzer\Analyzer\DependencyChecker;
use PhpMigrationAnalyzer\Database\DatabaseManager;
use PhpMigrationAnalyzer\Database\CacheManager;
use PhpMigrationAnalyzer\Reporter\ReporterInterface;
use PhpMigrationAnalyzer\Reporter\HTMLReporter;
use PhpMigrationAnalyzer\Reporter\MarkdownReporter;
use PhpMigrationAnalyzer\Reporter\TextReporter;
use PhpMigrationAnalyzer\Reporter\JSONReporter;

/**
 * Main Application class
 */
class Application
{
    private Container $container;
    private Config $config;
    private Logger $logger;

    public function __construct(string $configPath = null)
    {
        $this->container = new Container();

        // Load configuration
        if ($configPath === null) {
            $configPath = __DIR__ . '/../../config/config.php';
        }

        $this->config = Config::load($configPath);
        $this->container->instance('config', $this->config);

        // Set timezone
        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));

        // Register services
        $this->registerServices();

        // Initialize logger
        $this->logger = $this->container->get('logger');
    }

    /**
     * Register all services in the container
     */
    private function registerServices(): void
    {
        // Logger
        $this->container->set('logger', function ($c) {
            return new Logger($c->get('config'));
        });

        // Database Manager
        $this->container->set('database', function ($c) {
            return new DatabaseManager($c->get('config'));
        });

        // Cache Manager
        $this->container->set('cache', function ($c) {
            return new CacheManager($c->get('config'));
        });

        // Code Scanner
        $this->container->set('scanner', function ($c) {
            return new CodeScanner($c->get('config'), $c->get('logger'));
        });

        // PHP Version Analyzer
        $this->container->set('php_analyzer', function ($c) {
            return new PHPVersionAnalyzer(
                $c->get('database'),
                $c->get('config'),
                $c->get('logger')
            );
        });

        // Platform Analyzer
        $this->container->set('platform_analyzer', function ($c) {
            return new PlatformAnalyzer(
                $c->get('database'),
                $c->get('config'),
                $c->get('logger')
            );
        });

        // Plugin Detector
        $this->container->set('plugin_detector', function ($c) {
            return new PluginDetector(
                $c->get('config'),
                $c->get('logger')
            );
        });

        // Server Config Analyzer
        $this->container->set('server_analyzer', function ($c) {
            return new ServerConfigAnalyzer(
                $c->get('database'),
                $c->get('config'),
                $c->get('logger')
            );
        });

        // Dependency Checker
        $this->container->set('dependency_checker', function ($c) {
            return new DependencyChecker(
                $c->get('config'),
                $c->get('logger')
            );
        });
    }

    /**
     * Run analysis
     */
    public function analyze(array $options): array
    {
        $this->logger->info('Starting analysis', $options);

        $startTime = microtime(true);
        $scanId = $this->generateScanId();

        try {
            // Scan files
            $scanner = $this->container->get('scanner');
            $files = $scanner->scan($options['path'], $options['additional_paths'] ?? []);

            $this->logger->info("Found {$files['total']} files to analyze");

            $results = [
                'scan_id' => $scanId,
                'timestamp' => date('Y-m-d H:i:s'),
                'project_name' => $options['project_name'] ?? 'Unknown',
                'platform' => $options['platform'] ?? 'generic',
                'current_platform_version' => $options['current_platform_version'] ?? 'unknown',
                'target_platform_version' => $options['target_platform_version'] ?? 'unknown',
                'current_php_version' => $options['current_php'] ?? 'unknown',
                'target_php_version' => $options['target_php'] ?? 'unknown',
                'files' => $files,
                'issues' => [],
                'plugins' => [],
                'server_config' => [],
                'dependencies' => [],
                'summary' => [],
                'effort_estimation' => 0,
            ];

            // Analyze PHP version compatibility
            if (!empty($options['current_php']) && !empty($options['target_php'])) {
                $phpAnalyzer = $this->container->get('php_analyzer');
                $phpIssues = $phpAnalyzer->analyze(
                    $files['files'],
                    $options['current_php'],
                    $options['target_php']
                );
                $results['issues'] = array_merge($results['issues'], $phpIssues);
            }

            // Analyze platform compatibility
            if (!empty($options['platform']) && $options['platform'] !== 'generic') {
                $platformAnalyzer = $this->container->get('platform_analyzer');
                $platformIssues = $platformAnalyzer->analyze(
                    $files['files'],
                    $options['platform'],
                    $options['current_platform_version'] ?? null,
                    $options['target_platform_version'] ?? null
                );
                $results['issues'] = array_merge($results['issues'], $platformIssues);
            }

            // Detect custom plugins/modules
            if (!empty($options['detect_plugins'])) {
                $pluginDetector = $this->container->get('plugin_detector');
                $results['plugins'] = $pluginDetector->detect(
                    $options['path'],
                    $options['platform'] ?? 'generic'
                );
            }

            // Analyze server configuration
            if (!empty($options['check_server']) && !empty($options['server_config_path'])) {
                $serverAnalyzer = $this->container->get('server_analyzer');
                $results['server_config'] = $serverAnalyzer->analyze(
                    $options['server_config_path'],
                    $options['server_from'] ?? 'apache22',
                    $options['server_to'] ?? 'apache24'
                );
            }

            // Check dependencies
            if (!empty($options['check_dependencies'])) {
                $dependencyChecker = $this->container->get('dependency_checker');
                $composerPath = $options['path'] . '/composer.json';
                if (file_exists($composerPath)) {
                    $results['dependencies'] = $dependencyChecker->check($composerPath);
                }
            }

            // Generate summary
            $results['summary'] = $this->generateSummary($results);

            // Calculate effort estimation
            if (!empty($options['estimate_effort'])) {
                $results['effort_estimation'] = $this->estimateEffort($results['summary']);
            }

            $duration = microtime(true) - $startTime;
            $results['duration'] = round($duration, 2);

            $this->logger->info("Analysis completed in {$duration} seconds");

            return $results;

        } catch (\Exception $e) {
            $this->logger->error("Analysis failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique scan ID
     */
    private function generateScanId(): string
    {
        return date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * Generate summary from results
     */
    private function generateSummary(array $results): array
    {
        $summary = [
            'total_issues' => count($results['issues']),
            'by_severity' => [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'info' => 0,
            ],
            'by_category' => [],
        ];

        foreach ($results['issues'] as $issue) {
            $severity = $issue['severity'] ?? 'info';
            if (isset($summary['by_severity'][$severity])) {
                $summary['by_severity'][$severity]++;
            }

            $category = $issue['category'] ?? 'other';
            if (!isset($summary['by_category'][$category])) {
                $summary['by_category'][$category] = 0;
            }
            $summary['by_category'][$category]++;
        }

        return $summary;
    }

    /**
     * Estimate migration effort in hours
     */
    private function estimateEffort(array $summary): float
    {
        $hours = 0;
        $rates = $this->config->get('effort_estimation', [
            'critical' => 4,
            'high' => 2,
            'medium' => 1,
            'low' => 0.5,
            'info' => 0.1,
        ]);

        foreach ($summary['by_severity'] as $severity => $count) {
            $hours += $count * ($rates[$severity] ?? 1);
        }

        return round($hours, 1);
    }

    /**
     * Generate report
     */
    public function generateReport(array $results, string $format = 'html'): string
    {
        $reporter = $this->getReporter($format);
        return $reporter->generate($results);
    }

    /**
     * Get reporter instance
     */
    private function getReporter(string $format): ReporterInterface
    {
        switch ($format) {
            case 'html':
                return new HTMLReporter($this->config);
            case 'markdown':
                return new MarkdownReporter($this->config);
            case 'text':
                return new TextReporter($this->config);
            case 'json':
                return new JSONReporter($this->config);
            default:
                throw new \Exception("Unknown report format: {$format}");
        }
    }

    /**
     * Get container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
