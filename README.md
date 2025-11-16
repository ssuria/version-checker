# PHP Platform Migration Analyzer

A comprehensive tool for analyzing PHP code compatibility when migrating between PHP versions and different CMS/framework platforms.

## Features

- ✅ **PHP Version Analysis**: Detect incompatibilities between PHP 7.2 to 8.3
- ✅ **Platform-Specific Analysis**: Support for Moodle, WordPress, Magento/OpenMage, PrestaShop, Symfony, Laravel
- ✅ **Custom Plugin Detection**: Automatically detect non-core plugins and modules
- ✅ **Server Configuration Analysis**: Convert .htaccess between Apache 2.2, Apache 2.4, Nginx, and FrankenPHP
- ✅ **Professional Reports**: Generate reports in HTML, Markdown, Text, and JSON formats
- ✅ **Web Interface**: Complete web UI for easy analysis
- ✅ **CLI Tool**: Command-line interface for automation and CI/CD integration
- ✅ **Effort Estimation**: Calculate estimated migration hours based on issues found

## Installation

### Requirements

- PHP 7.4 or higher
- Composer

### Install Dependencies

```bash
composer install
```

## Usage

### Command Line Interface

#### Basic Analysis

```bash
php bin/analyze /path/to/project \
    --current-php=7.2 \
    --target-php=8.1 \
    --format=html \
    --output=report.html
```

#### Full Moodle Analysis

```bash
php bin/analyze /var/www/moodle \
    --platform=moodle \
    --current-platform=3.8 \
    --target-platform=4.3 \
    --current-php=7.2 \
    --target-php=8.1 \
    --detect-plugins \
    --check-dependencies \
    --estimate-effort \
    --format=html \
    --output=moodle-migration-report.html
```

## Project Structure

```
php-migration-analyzer/
├── bin/analyze              # CLI executable
├── src/                     # Source code
│   ├── Core/               # Application core
│   ├── Analyzer/           # Analysis engines
│   ├── Parser/             # PHP code parsers
│   ├── Reporter/           # Report generators
│   └── Utils/              # Utilities
├── database/               # Data files
├── config/                 # Configuration
└── storage/                # Temporary storage
```

## License

MIT License

---

**Made with ❤️ for the PHP community**
