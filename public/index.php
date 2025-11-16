<?php

/**
 * PHP Migration Analyzer - Web Interface Entry Point
 */

// Autoload
require __DIR__ . '/../vendor/autoload.php';

use PhpMigrationAnalyzer\Core\Application;

// Simple router
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Serve static assets
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $requestPath)) {
    return false; // Let PHP built-in server handle static files
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Migration Analyzer</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🔍 PHP Migration Analyzer</h1>
            <p>Analyze PHP code compatibility across versions and platforms</p>
        </header>

        <div class="card">
            <h2>Quick Analysis</h2>
            <p>Use the command-line interface for full analysis capabilities:</p>

            <div class="code-block">
                <code>php bin/analyze /path/to/project --current-php=7.4 --target-php=8.1 --format=html --output=report.html</code>
            </div>

            <h3>Supported Platforms</h3>
            <ul>
                <li><strong>Moodle</strong> - Learning Management System</li>
                <li><strong>WordPress</strong> - Content Management System</li>
                <li><strong>Magento / OpenMage</strong> - eCommerce Platform</li>
                <li><strong>PrestaShop</strong> - eCommerce Platform</li>
                <li><strong>Symfony</strong> - PHP Framework</li>
                <li><strong>Laravel</strong> - PHP Framework</li>
                <li><strong>Generic PHP</strong> - Any PHP application</li>
            </ul>

            <h3>PHP Versions Supported</h3>
            <p>Analyze compatibility between PHP 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, and 8.3</p>

            <h3>Features</h3>
            <ul>
                <li>✅ Detect removed and deprecated PHP functions</li>
                <li>✅ Analyze platform-specific compatibility issues</li>
                <li>✅ Detect custom plugins and modules</li>
                <li>✅ Convert server configurations (Apache, Nginx, FrankenPHP)</li>
                <li>✅ Generate professional reports (HTML, Markdown, JSON, Text)</li>
                <li>✅ Estimate migration effort in hours</li>
            </ul>

            <h3>Example Commands</h3>

            <div class="examples">
                <div class="example">
                    <h4>Moodle Analysis</h4>
                    <div class="code-block">
                        <code>php bin/analyze /var/www/moodle \<br>
                        &nbsp;&nbsp;--platform=moodle \<br>
                        &nbsp;&nbsp;--current-platform=3.8 \<br>
                        &nbsp;&nbsp;--target-platform=4.3 \<br>
                        &nbsp;&nbsp;--current-php=7.2 \<br>
                        &nbsp;&nbsp;--target-php=8.1 \<br>
                        &nbsp;&nbsp;--detect-plugins \<br>
                        &nbsp;&nbsp;--estimate-effort \<br>
                        &nbsp;&nbsp;--format=html \<br>
                        &nbsp;&nbsp;--output=report.html</code>
                    </div>
                </div>

                <div class="example">
                    <h4>WordPress with Server Config</h4>
                    <div class="code-block">
                        <code>php bin/analyze /var/www/wordpress \<br>
                        &nbsp;&nbsp;--platform=wordpress \<br>
                        &nbsp;&nbsp;--current-php=7.4 \<br>
                        &nbsp;&nbsp;--target-php=8.2 \<br>
                        &nbsp;&nbsp;--check-server \<br>
                        &nbsp;&nbsp;--server-config-path=.htaccess \<br>
                        &nbsp;&nbsp;--server-from=apache22 \<br>
                        &nbsp;&nbsp;--server-to=nginx \<br>
                        &nbsp;&nbsp;--format=html</code>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>PHP Migration Analyzer v1.0.0 | MIT License</p>
        </footer>
    </div>
</body>
</html>
