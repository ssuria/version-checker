<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Database\DatabaseManager;
use PhpMigrationAnalyzer\Parser\PHPParser;
use PhpMigrationAnalyzer\Parser\FunctionCallExtractor;
use PhpMigrationAnalyzer\Parser\ClassUsageExtractor;
use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Analyze PHP version compatibility
 */
class PHPVersionAnalyzer
{
    private DatabaseManager $database;
    private Config $config;
    private Logger $logger;
    private PHPParser $parser;

    public function __construct(DatabaseManager $database, Config $config, Logger $logger)
    {
        $this->database = $database;
        $this->config = $config;
        $this->logger = $logger;
        $this->parser = new PHPParser();
    }

    /**
     * Analyze files for PHP version compatibility
     *
     * @param array $files List of files to analyze
     * @param string $fromVersion Starting PHP version
     * @param string $toVersion Target PHP version
     * @return array List of issues found
     */
    public function analyze(array $files, string $fromVersion, string $toVersion): array
    {
        $this->logger->info("Analyzing PHP compatibility: {$fromVersion} -> {$toVersion}");

        $issues = [];

        // Get all PHP changes between versions
        $changes = $this->database->getAllPhpChanges($fromVersion, $toVersion);

        if (empty($changes)) {
            $this->logger->warning("No PHP change data found for {$fromVersion} -> {$toVersion}");
            return $issues;
        }

        // Analyze each file
        foreach ($files as $fileInfo) {
            $filePath = $fileInfo['path'];
            $fileIssues = $this->analyzeFile($filePath, $changes);

            foreach ($fileIssues as $issue) {
                $issue['file'] = $filePath;
                $issue['relative_path'] = $fileInfo['relative_path'] ?? $filePath;
                $issues[] = $issue;
            }
        }

        $this->logger->info("Found " . count($issues) . " PHP compatibility issues");

        return $issues;
    }

    /**
     * Analyze a single file
     */
    private function analyzeFile(string $filePath, array $changes): array
    {
        $issues = [];

        $content = file_get_contents($filePath);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        // Parse file to AST
        $ast = $this->parser->parseFile($filePath);

        if ($ast === null) {
            // Parse error - could be a syntax issue
            return $issues;
        }

        // Extract function calls
        $functionExtractor = new FunctionCallExtractor();
        $functionExtractor->setCurrentFile($filePath);
        $this->parser->traverse($ast, $functionExtractor);
        $functionCalls = $functionExtractor->getFunctionCalls();

        // Extract class usages
        $classExtractor = new ClassUsageExtractor();
        $classExtractor->setCurrentFile($filePath);
        $this->parser->traverse($ast, $classExtractor);
        $classUsages = $classExtractor->getClassUsages();

        // Check for removed functions
        foreach ($changes['removed_functions'] ?? [] as $removedFunc) {
            $issues = array_merge(
                $issues,
                $this->checkRemovedFunction($removedFunc, $functionCalls, $content, $lines)
            );
        }

        // Check for deprecated features
        foreach ($changes['deprecated_features'] ?? [] as $deprecated) {
            $issues = array_merge(
                $issues,
                $this->checkDeprecatedFeature($deprecated, $content, $lines)
            );
        }

        // Check for behavior changes
        foreach ($changes['behavior_changes'] ?? [] as $behaviorChange) {
            $issues = array_merge(
                $issues,
                $this->checkBehaviorChange($behaviorChange, $content, $lines)
            );
        }

        return $issues;
    }

    /**
     * Check for removed function usage
     */
    private function checkRemovedFunction(array $removedFunc, array $functionCalls, string $content, array $lines): array
    {
        $issues = [];
        $functionName = $removedFunc['function'];

        // Check if function is used
        foreach ($functionCalls as $call) {
            if (strcasecmp($call['name'], $functionName) === 0) {
                $lineNumber = $call['line'];

                $issues[] = [
                    'type' => 'removed_function',
                    'category' => 'php_compatibility',
                    'severity' => $removedFunc['severity'] ?? 'critical',
                    'title' => "Removed function: {$functionName}()",
                    'description' => $removedFunc['description'] ?? "Function {$functionName}() has been removed",
                    'line' => $lineNumber,
                    'code' => $lines[$lineNumber - 1] ?? '',
                    'snippet' => Helpers::extractCodeSnippet($lines, $lineNumber, 2),
                    'replacement' => $removedFunc['replacement'] ?? null,
                    'example_old' => $removedFunc['example_old'] ?? null,
                    'example_new' => $removedFunc['example_new'] ?? null,
                ];
            }
        }

        // Also check with regex if provided
        if (isset($removedFunc['regex']) && !empty($removedFunc['regex'])) {
            if (preg_match_all('/' . $removedFunc['regex'] . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $lineNumber = substr_count($content, "\n", 0, $offset) + 1;

                    // Check if we already added this issue
                    $alreadyAdded = false;
                    foreach ($issues as $issue) {
                        if ($issue['line'] === $lineNumber) {
                            $alreadyAdded = true;
                            break;
                        }
                    }

                    if (!$alreadyAdded) {
                        $issues[] = [
                            'type' => 'removed_function',
                            'category' => 'php_compatibility',
                            'severity' => $removedFunc['severity'] ?? 'critical',
                            'title' => "Removed function: {$functionName}()",
                            'description' => $removedFunc['description'] ?? "Function {$functionName}() has been removed",
                            'line' => $lineNumber,
                            'code' => $lines[$lineNumber - 1] ?? '',
                            'snippet' => Helpers::extractCodeSnippet($lines, $lineNumber, 2),
                            'replacement' => $removedFunc['replacement'] ?? null,
                            'example_old' => $removedFunc['example_old'] ?? null,
                            'example_new' => $removedFunc['example_new'] ?? null,
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for deprecated feature usage
     */
    private function checkDeprecatedFeature(array $deprecated, string $content, array $lines): array
    {
        $issues = [];

        if (!isset($deprecated['regex'])) {
            return $issues;
        }

        if (preg_match_all('/' . $deprecated['regex'] . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $offset = $match[1];
                $lineNumber = substr_count($content, "\n", 0, $offset) + 1;

                $issues[] = [
                    'type' => 'deprecated_feature',
                    'category' => 'php_compatibility',
                    'severity' => $deprecated['severity'] ?? 'high',
                    'title' => $deprecated['title'] ?? 'Deprecated feature',
                    'description' => $deprecated['description'] ?? '',
                    'line' => $lineNumber,
                    'code' => $lines[$lineNumber - 1] ?? '',
                    'snippet' => Helpers::extractCodeSnippet($lines, $lineNumber, 2),
                    'replacement' => $deprecated['replacement'] ?? null,
                    'example_old' => $deprecated['example_old'] ?? null,
                    'example_new' => $deprecated['example_new'] ?? null,
                ];
            }
        }

        return $issues;
    }

    /**
     * Check for behavior changes
     */
    private function checkBehaviorChange(array $behaviorChange, string $content, array $lines): array
    {
        $issues = [];

        if (!isset($behaviorChange['regex'])) {
            return $issues;
        }

        if (preg_match_all('/' . $behaviorChange['regex'] . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $offset = $match[1];
                $lineNumber = substr_count($content, "\n", 0, $offset) + 1;

                $issues[] = [
                    'type' => 'behavior_change',
                    'category' => 'php_compatibility',
                    'severity' => $behaviorChange['severity'] ?? 'medium',
                    'title' => $behaviorChange['title'] ?? 'Behavior change',
                    'description' => $behaviorChange['description'] ?? '',
                    'line' => $lineNumber,
                    'code' => $lines[$lineNumber - 1] ?? '',
                    'snippet' => Helpers::extractCodeSnippet($lines, $lineNumber, 2),
                    'recommendation' => $behaviorChange['recommendation'] ?? null,
                ];
            }
        }

        return $issues;
    }
}
