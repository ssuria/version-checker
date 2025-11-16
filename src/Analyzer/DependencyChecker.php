<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Logger;
use PhpMigrationAnalyzer\Utils\Helpers;

/**
 * Check composer dependencies for compatibility
 */
class DependencyChecker
{
    private Config $config;
    private Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Check composer.json for dependency issues
     *
     * @param string $composerPath Path to composer.json
     * @return array Dependency analysis results
     */
    public function check(string $composerPath): array
    {
        $this->logger->info("Checking dependencies: {$composerPath}");

        if (!file_exists($composerPath)) {
            return [
                'success' => false,
                'error' => 'composer.json not found',
            ];
        }

        $composerData = Helpers::readJson($composerPath);

        if ($composerData === null) {
            return [
                'success' => false,
                'error' => 'Invalid composer.json file',
            ];
        }

        $result = [
            'success' => true,
            'file' => $composerPath,
            'packages' => [],
            'issues' => [],
            'php_requirement' => null,
        ];

        // Check PHP requirement
        if (isset($composerData['require']['php'])) {
            $result['php_requirement'] = $composerData['require']['php'];
        }

        // Collect all packages
        $packages = array_merge(
            $composerData['require'] ?? [],
            $composerData['require-dev'] ?? []
        );

        foreach ($packages as $package => $version) {
            $packageInfo = [
                'name' => $package,
                'version' => $version,
                'type' => isset($composerData['require'][$package]) ? 'require' : 'require-dev',
                'issues' => [],
            ];

            // Check for outdated version constraints
            if ($this->isOutdatedConstraint($version)) {
                $packageInfo['issues'][] = [
                    'type' => 'outdated_constraint',
                    'severity' => 'medium',
                    'message' => 'Version constraint may be outdated',
                ];
            }

            // Check for wildcard versions
            if (strpos($version, '*') !== false) {
                $packageInfo['issues'][] = [
                    'type' => 'wildcard_version',
                    'severity' => 'high',
                    'message' => 'Wildcard version constraints are not recommended',
                ];
            }

            // Check for dev stability
            if (strpos($version, 'dev-') === 0 || strpos($version, '@dev') !== false) {
                $packageInfo['issues'][] = [
                    'type' => 'dev_stability',
                    'severity' => 'medium',
                    'message' => 'Development version detected - may be unstable',
                ];
            }

            $result['packages'][] = $packageInfo;

            if (!empty($packageInfo['issues'])) {
                foreach ($packageInfo['issues'] as $issue) {
                    $result['issues'][] = array_merge($issue, [
                        'package' => $package,
                        'version' => $version,
                    ]);
                }
            }
        }

        $this->logger->info("Found " . count($result['packages']) . " packages");

        return $result;
    }

    /**
     * Check if version constraint is outdated
     */
    private function isOutdatedConstraint(string $version): bool
    {
        // Check for very old version patterns
        if (preg_match('/^[<>=]+\s*[0-4]\./', $version)) {
            return true;
        }

        return false;
    }

    /**
     * Get dependency tree (simplified)
     */
    public function getDependencyTree(string $composerPath): array
    {
        $composerData = Helpers::readJson($composerPath);

        if ($composerData === null) {
            return [];
        }

        $tree = [
            'direct' => [],
            'dev' => [],
        ];

        if (isset($composerData['require'])) {
            $tree['direct'] = $composerData['require'];
        }

        if (isset($composerData['require-dev'])) {
            $tree['dev'] = $composerData['require-dev'];
        }

        return $tree;
    }
}
