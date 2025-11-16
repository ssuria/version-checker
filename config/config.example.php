<?php

/**
 * PHP Migration Analyzer Configuration Example
 *
 * Copy this file to config.php and adjust the values as needed
 */

return [
    // Application settings
    'app' => [
        'name' => 'PHP Migration Analyzer',
        'version' => '1.0.0',
        'env' => 'production', // development, production
        'debug' => false,
        'timezone' => 'UTC',
    ],

    // Storage paths
    'paths' => [
        'storage' => __DIR__ . '/../storage',
        'scans' => __DIR__ . '/../storage/scans',
        'cache' => __DIR__ . '/../storage/cache',
        'logs' => __DIR__ . '/../storage/logs',
        'database' => __DIR__ . '/../database',
        'templates' => __DIR__ . '/../templates',
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'driver' => 'file', // file, redis, memcached
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'analyzer.log',
    ],

    // Analysis settings
    'analysis' => [
        'max_file_size' => 5242880, // 5MB
        'timeout' => 300, // 5 minutes
        'memory_limit' => '512M',
        'exclude_patterns' => [
            '*/vendor/*',
            '*/node_modules/*',
            '*/.git/*',
            '*/cache/*',
            '*/temp/*',
            '*/tmp/*',
        ],
        'file_extensions' => ['php', 'inc', 'module', 'install'],
    ],

    // Platform detection
    'platforms' => [
        'moodle' => [
            'detection_files' => ['version.php', 'config-dist.php'],
            'detection_patterns' => ['$CFG->version', 'moodle'],
            'core_paths' => ['lib/', 'admin/', 'course/', 'user/', 'enrol/'],
            'custom_paths' => ['local/', 'mod/', 'blocks/', 'theme/', 'report/', 'auth/', 'filter/'],
        ],
        'wordpress' => [
            'detection_files' => ['wp-config.php', 'wp-load.php'],
            'detection_patterns' => ['WP_VERSION', 'ABSPATH'],
            'core_paths' => ['wp-admin/', 'wp-includes/'],
            'custom_paths' => ['wp-content/plugins/', 'wp-content/themes/'],
        ],
        'magento' => [
            'detection_files' => ['app/Mage.php', 'composer.json'],
            'detection_patterns' => ['Mage::getVersion', 'magento/product'],
            'core_paths' => ['app/code/core/', 'lib/'],
            'custom_paths' => ['app/code/local/', 'app/code/community/'],
        ],
        'prestashop' => [
            'detection_files' => ['config/config.inc.php', 'index.php'],
            'detection_patterns' => ['_PS_VERSION_', 'PrestaShop'],
            'core_paths' => ['classes/', 'controllers/', 'admin/'],
            'custom_paths' => ['modules/', 'themes/', 'override/'],
        ],
        'symfony' => [
            'detection_files' => ['composer.json', 'symfony.lock'],
            'detection_patterns' => ['symfony/framework-bundle'],
            'core_paths' => ['vendor/symfony/'],
            'custom_paths' => ['src/', 'config/'],
        ],
        'laravel' => [
            'detection_files' => ['artisan', 'composer.json'],
            'detection_patterns' => ['laravel/framework'],
            'core_paths' => ['vendor/laravel/'],
            'custom_paths' => ['app/', 'routes/', 'resources/'],
        ],
    ],

    // Report settings
    'reports' => [
        'default_format' => 'html',
        'include_examples' => true,
        'include_file_paths' => true,
        'max_examples_per_issue' => 5,
    ],

    // Effort estimation (hours per issue by severity)
    'effort_estimation' => [
        'critical' => 4,
        'high' => 2,
        'medium' => 1,
        'low' => 0.5,
        'info' => 0.1,
    ],

    // API endpoints for checking plugin updates
    'api_endpoints' => [
        'moodle' => 'https://moodle.org/plugins/services.php',
        'wordpress' => 'https://api.wordpress.org/plugins/info/1.2/',
    ],

    // Web interface
    'web' => [
        'base_url' => '',
        'assets_path' => '/assets',
        'upload_max_size' => 10485760, // 10MB
    ],
];
