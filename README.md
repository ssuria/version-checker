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

📦 Cuando salga PHP 8.4
✅ NO necesitará cambios (Arquitectura preparada)
Todo el código de análisis ya está listo:

✅ PHPVersionAnalyzer: Lee cualquier archivo JSON
✅ Reporters: Funcionan con cualquier versión
✅ CLI y Web: No dependen de versiones específicas
✅ Parsers y Analyzers: Son agnósticos a la versión
📝 SÍ necesitará actualización (Solo datos)
1. Agregar nuevo archivo JSON (5-10 minutos)
# Crear:
database/php-changes/8.3-to-8.4.json
{
  "version_from": "8.3",
  "version_to": "8.4",
  "removed_functions": [
    {
      "function": "nueva_funcion_removida",
      "severity": "critical",
      "description": "...",
      "replacement": "...",
      "regex": "\\bnueva_funcion_removida\\s*\\("
    }
  ],
  "deprecated_features": [...],
  "behavior_changes": [...],
  "new_features": [...]
}
2. Actualizar DatabaseManager (1 línea)
// src/Database/DatabaseManager.php línea ~159
public function getAvailablePhpVersions(): array
{
    return ['7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4']; // ← Agregar 8.4
}
3. Actualizar opciones CLI (opcional, 1 línea)
// bin/analyze
->addOption('target-php', null, InputOption::VALUE_REQUIRED, 
    'Target PHP version', '8.1') // ← Cambiar default a 8.4 si quieres
🎯 Proceso de Actualización (Total: ~30 minutos)
# 1. Consultar changelog oficial de PHP 8.4
curl https://www.php.net/releases/8_4_0.php

# 2. Crear archivo JSON con los cambios
vim database/php-changes/8.3-to-8.4.json

# 3. Actualizar array de versiones
vim src/Database/DatabaseManager.php

# 4. Probar
php bin/analyze /path/test --current-php=8.3 --target-php=8.4

# 5. Commit
git add database/php-changes/8.3-to-8.4.json
git commit -m "Add PHP 8.4 compatibility checks"
🏗️ ¿Por qué está bien diseñado?
La arquitectura usa separación de datos y lógica:

[Datos JSON] ─────> [DatabaseManager] ─────> [Analyzer] ─────> [Report]
    ↑                      ↑                      ↑                ↑
  Cambias esto      Lee automático        Procesa igual      Genera igual
📊 Comparación con otros sistemas
| Sistema | Actualizar PHP 8.4 | |---------|-------------------| | Este proyecto | ✅ 1 archivo JSON + 1 línea código | | Código hardcoded | ❌ Reescribir lógica, tests, etc. | | Sin extensibilidad | ❌ Fork completo del proyecto |

🔮 Futuro a largo plazo
Si en 5 años tienes PHP 9.0:

// Simplemente agregar:
database/php-changes/8.4-to-9.0.json
database/php-changes/9.0-to-9.1.json
...
El código no toca nada más.

💡 Bonus: Hacer más fácil la actualización
Si quieres hacerlo aún más mantenible, podrías crear un script helper:

// scripts/add-php-version.php
<?php
$from = $argv[1]; // 8.3
$to = $argv[2];   // 8.4

$template = [
    'version_from' => $from,
    'version_to' => $to,
    'removed_functions' => [],
    'deprecated_features' => [],
    'behavior_changes' => [],
    'new_features' => []
];

file_put_contents(
    "database/php-changes/{$from}-to-{$to}.json",
    json_encode($template, JSON_PRETTY_PRINT)
);

echo "✓ Created template for PHP {$from} → {$to}\n";
echo "Now edit the file and add the changes from php.net\n";
# Uso futuro:
php scripts/add-php-version.php 8.3 8.4
