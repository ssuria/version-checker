<?php

namespace PhpMigrationAnalyzer\Analyzer;

use PhpMigrationAnalyzer\Core\Config;
use PhpMigrationAnalyzer\Utils\Logger;

/**
 * Detect custom plugins/modules/themes for different platforms
 */
class PluginDetector
{
    private Config $config;
    private Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Detect custom plugins/modules
     *
     * @param string $basePath Base path of the installation
     * @param string $platform Platform name
     * @return array List of detected plugins
     */
    public function detect(string $basePath, string $platform): array
    {
        $this->logger->info("Detecting plugins for platform: {$platform}");

        $plugins = [];

        switch ($platform) {
            case 'moodle':
                $plugins = $this->detectMoodlePlugins($basePath);
                break;
            case 'wordpress':
                $plugins = $this->detectWordPressPlugins($basePath);
                break;
            case 'magento':
                $plugins = $this->detectMagentoModules($basePath);
                break;
            case 'prestashop':
                $plugins = $this->detectPrestaShopModules($basePath);
                break;
            case 'symfony':
            case 'laravel':
                $plugins = $this->detectComposerPackages($basePath);
                break;
            default:
                $this->logger->warning("Unknown platform: {$platform}");
        }

        $this->logger->info("Found " . count($plugins) . " plugins/modules");

        return $plugins;
    }

    /**
     * Detect Moodle plugins
     */
    private function detectMoodlePlugins(string $basePath): array
    {
        $plugins = [];
        $pluginTypes = ['local', 'mod', 'blocks', 'theme', 'report', 'auth', 'filter', 'enrol', 'assignsubmission', 'assignfeedback'];

        foreach ($pluginTypes as $type) {
            $typePath = $basePath . '/' . $type;

            if (!is_dir($typePath)) {
                continue;
            }

            $dirs = glob($typePath . '/*', GLOB_ONLYDIR);

            foreach ($dirs as $dir) {
                $pluginName = basename($dir);
                $versionFile = $dir . '/version.php';

                if (file_exists($versionFile)) {
                    $pluginInfo = $this->parseMoodleVersionFile($versionFile);

                    $plugins[] = [
                        'type' => $type,
                        'name' => $pluginName,
                        'full_name' => "{$type}_{$pluginName}",
                        'path' => $dir,
                        'version' => $pluginInfo['version'] ?? 'unknown',
                        'requires' => $pluginInfo['requires'] ?? null,
                        'is_custom' => true,
                    ];
                }
            }
        }

        return $plugins;
    }

    /**
     * Parse Moodle version.php file
     */
    private function parseMoodleVersionFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $info = [];

        // Extract version
        if (preg_match('/\$plugin->version\s*=\s*(\d+);/', $content, $matches)) {
            $info['version'] = $matches[1];
        }

        // Extract requires
        if (preg_match('/\$plugin->requires\s*=\s*(\d+);/', $content, $matches)) {
            $info['requires'] = $matches[1];
        }

        // Extract component
        if (preg_match('/\$plugin->component\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $matches)) {
            $info['component'] = $matches[1];
        }

        return $info;
    }

    /**
     * Detect WordPress plugins
     */
    private function detectWordPressPlugins(string $basePath): array
    {
        $plugins = [];
        $pluginsPath = $basePath . '/wp-content/plugins';

        if (!is_dir($pluginsPath)) {
            return $plugins;
        }

        $dirs = glob($pluginsPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $pluginName = basename($dir);

            // Skip default plugins
            if (in_array($pluginName, ['akismet', 'hello'])) {
                continue;
            }

            // Find main plugin file
            $files = glob($dir . '/*.php');
            $pluginInfo = null;

            foreach ($files as $file) {
                $content = file_get_contents($file, false, null, 0, 8192);
                if (strpos($content, 'Plugin Name:') !== false) {
                    $pluginInfo = $this->parseWordPressPluginHeader($content);
                    break;
                }
            }

            $plugins[] = [
                'type' => 'plugin',
                'name' => $pluginName,
                'full_name' => $pluginInfo['name'] ?? $pluginName,
                'path' => $dir,
                'version' => $pluginInfo['version'] ?? 'unknown',
                'is_custom' => true,
            ];
        }

        // Detect themes
        $themesPath = $basePath . '/wp-content/themes';

        if (is_dir($themesPath)) {
            $themeDirs = glob($themesPath . '/*', GLOB_ONLYDIR);

            foreach ($themeDirs as $dir) {
                $themeName = basename($dir);

                // Skip default themes
                if (preg_match('/^twenty(ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|twentyone|twentytwo|twentythree|twentyfour)$/', $themeName)) {
                    continue;
                }

                $styleFile = $dir . '/style.css';
                $themeInfo = null;

                if (file_exists($styleFile)) {
                    $content = file_get_contents($styleFile, false, null, 0, 8192);
                    $themeInfo = $this->parseWordPressThemeHeader($content);
                }

                $plugins[] = [
                    'type' => 'theme',
                    'name' => $themeName,
                    'full_name' => $themeInfo['name'] ?? $themeName,
                    'path' => $dir,
                    'version' => $themeInfo['version'] ?? 'unknown',
                    'is_custom' => true,
                ];
            }
        }

        return $plugins;
    }

    /**
     * Parse WordPress plugin header
     */
    private function parseWordPressPluginHeader(string $content): array
    {
        $info = [];

        if (preg_match('/Plugin Name:\s*(.+)/', $content, $matches)) {
            $info['name'] = trim($matches[1]);
        }

        if (preg_match('/Version:\s*(.+)/', $content, $matches)) {
            $info['version'] = trim($matches[1]);
        }

        return $info;
    }

    /**
     * Parse WordPress theme header
     */
    private function parseWordPressThemeHeader(string $content): array
    {
        $info = [];

        if (preg_match('/Theme Name:\s*(.+)/', $content, $matches)) {
            $info['name'] = trim($matches[1]);
        }

        if (preg_match('/Version:\s*(.+)/', $content, $matches)) {
            $info['version'] = trim($matches[1]);
        }

        return $info;
    }

    /**
     * Detect Magento modules
     */
    private function detectMagentoModules(string $basePath): array
    {
        $plugins = [];

        // Magento 1.x
        $localPath = $basePath . '/app/code/local';
        $communityPath = $basePath . '/app/code/community';

        foreach ([$localPath, $communityPath] as $codePath) {
            if (!is_dir($codePath)) {
                continue;
            }

            $vendors = glob($codePath . '/*', GLOB_ONLYDIR);

            foreach ($vendors as $vendorDir) {
                $modules = glob($vendorDir . '/*', GLOB_ONLYDIR);

                foreach ($modules as $moduleDir) {
                    $vendor = basename($vendorDir);
                    $module = basename($moduleDir);

                    $plugins[] = [
                        'type' => 'module',
                        'name' => $module,
                        'full_name' => "{$vendor}_{$module}",
                        'path' => $moduleDir,
                        'vendor' => $vendor,
                        'is_custom' => true,
                    ];
                }
            }
        }

        return $plugins;
    }

    /**
     * Detect PrestaShop modules
     */
    private function detectPrestaShopModules(string $basePath): array
    {
        $plugins = [];
        $modulesPath = $basePath . '/modules';

        if (!is_dir($modulesPath)) {
            return $plugins;
        }

        $dirs = glob($modulesPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $moduleName = basename($dir);
            $mainFile = $dir . '/' . $moduleName . '.php';

            $version = 'unknown';

            if (file_exists($mainFile)) {
                $content = file_get_contents($mainFile, false, null, 0, 8192);

                if (preg_match('/\$this->version\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $version = $matches[1];
                }
            }

            $plugins[] = [
                'type' => 'module',
                'name' => $moduleName,
                'full_name' => $moduleName,
                'path' => $dir,
                'version' => $version,
                'is_custom' => true,
            ];
        }

        return $plugins;
    }

    /**
     * Detect Composer packages (for Symfony/Laravel)
     */
    private function detectComposerPackages(string $basePath): array
    {
        $plugins = [];
        $composerFile = $basePath . '/composer.json';

        if (!file_exists($composerFile)) {
            return $plugins;
        }

        $content = file_get_contents($composerFile);
        $data = json_decode($content, true);

        if (!$data) {
            return $plugins;
        }

        $packages = array_merge(
            $data['require'] ?? [],
            $data['require-dev'] ?? []
        );

        foreach ($packages as $package => $version) {
            // Skip PHP and extensions
            if (strpos($package, '/') === false) {
                continue;
            }

            // Skip framework packages
            if (strpos($package, 'symfony/') === 0 || strpos($package, 'laravel/') === 0) {
                continue;
            }

            $plugins[] = [
                'type' => 'package',
                'name' => $package,
                'full_name' => $package,
                'version' => $version,
                'is_custom' => false,
            ];
        }

        return $plugins;
    }
}
