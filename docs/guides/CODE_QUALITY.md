# 🎯 Code Quality Tools - PHP-CS-Fixer & PHPStan

> **TL;DR**: Automated code quality tools for backend: PHP-CS-Fixer (code style) and PHPStan (static analysis). Configured for PHP 8.3 with Symfony and Doctrine support. Git hooks run checks automatically before commits.

---

## 📋 Table of Contents

- [Overview](#overview)
- [PHP-CS-Fixer (Code Style)](#php-cs-fixer-code-style)
- [PHPStan (Static Analysis)](#phpstan-static-analysis)
- [Git Pre-Commit Hooks](#git-pre-commit-hooks)
- [Makefile Commands](#makefile-commands)
- [Configuration Details](#configuration-details)
- [Troubleshooting](#troubleshooting)

---

## Overview

This project uses two essential code quality tools:

1. **PHP-CS-Fixer** - Automatically fixes code style issues (PSR-12 + modern PHP 8.3)
2. **PHPStan** - Static analysis tool that finds bugs without running code (level 5)

Both tools are:
- ✅ Integrated into development workflow via Makefile
- ✅ Run automatically via Git pre-commit hooks
- ✅ Configured for Symfony + Doctrine
- ✅ Optimized for PHP 8.3 features

---

## PHP-CS-Fixer (Code Style)

### What it does

PHP-CS-Fixer automatically formats PHP code according to:
- **PSR-12** standard (PHP-FIG coding standard)
- **Symfony** conventions
- **Modern PHP 8.3** features (strict types, readonly, enums, match)

### Quick Usage

```bash
# Check code style (dry-run, shows what would be fixed)
make cs-fixer-check

# Fix code style automatically
make cs-fixer-fix
```

### Detailed Usage

```bash
# Check all files (dry-run)
docker exec backend-php83 vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# Check specific directory
docker exec backend-php83 vendor/bin/php-cs-fixer fix src/Controller --dry-run --diff

# Fix all files
docker exec backend-php83 vendor/bin/php-cs-fixer fix --verbose

# Fix specific file
docker exec backend-php83 vendor/bin/php-cs-fixer fix src/Controller/TaskController.php
```

### What gets fixed

**Before:**
```php
<?php
namespace App\Controller;
use Symfony\Component\HttpFoundation\Response;
class TaskController {
    public function index(): Response {
        $data = array('foo' => 'bar');
        return new Response($data);
    }
}
```

**After:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

final class TaskController
{
    public function index(): Response
    {
        $data = ['foo' => 'bar'];

        return new Response($data);
    }
}
```

### Key Rules Applied

- ✅ `declare(strict_types=1)` on all files
- ✅ Short array syntax `[]` instead of `array()`
- ✅ Trailing commas in multiline arrays/arguments
- ✅ Binary operators alignment (`=>`)
- ✅ Ordered class elements (properties → constructor → methods)
- ✅ PHPDoc formatting and alignment
- ✅ No unused imports
- ✅ Proper spacing and indentation

### Configuration

**File:** `apps/backend/.php-cs-fixer.php`

```php
return (new Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        // ... 100+ rules
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Paths analyzed:**
- `apps/backend/src/`
- `apps/backend/tests/`

---

## PHPStan (Static Analysis)

### What it does

PHPStan performs static code analysis to find:
- Type mismatches
- Undefined variables/methods
- Dead code
- Logic errors
- Missing return types
- Null pointer dereferences

### Quick Usage

```bash
# Run analysis
make phpstan

# Generate baseline (ignore existing errors)
make phpstan-baseline
```

### Detailed Usage

```bash
# Run full analysis
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=1G

# Analyze specific path
docker exec backend-php83 vendor/bin/phpstan analyse src/Controller

# Clear result cache
docker exec backend-php83 vendor/bin/phpstan clear-result-cache

# Generate baseline file
docker exec backend-php83 vendor/bin/phpstan analyse --generate-baseline
```

### Analysis Levels

PHPStan has 10 levels (0-9), each progressively stricter:

| Level | Description                          | Current |
|-------|--------------------------------------|---------|
| 0     | Basic checks                         |         |
| 1     | Unknown classes                      |         |
| 2     | Unknown methods                      |         |
| 3     | Unknown properties/method returns    |         |
| 4     | Basic dead code                      |         |
| **5** | **Arguments/return types**           | ✅ Yes  |
| 6     | Missing type hints                   |         |
| 7     | Unions and nullable types            |         |
| 8     | Call to methods on nullable types    |         |
| 9     | Mixed types not allowed              |         |

**Current level:** 5 (good balance between strictness and practicality)

### Example Errors Caught

**Type mismatch:**
```php
// ❌ PHPStan error
public function setStatus(string $status): void
{
    $this->status = 123; // Expected string, got int
}
```

**Undefined method:**
```php
// ❌ PHPStan error
$task = $taskRepository->find($id);
$task->getName(); // Method getName() not found on Task|null
```

**Missing return type:**
```php
// ❌ PHPStan error (level 5+)
public function calculate($a, $b) // Missing return type
{
    return $a + $b;
}
```

### Configuration

**File:** `apps/backend/phpstan.neon`

```neon
parameters:
    level: 5
    paths:
        - src
        - tests
    symfony:
        container_xml_path: var/cache/dev/App_KernelDevDebugContainer.xml
    doctrine:
        repositoryClass: App\Repository\BaseRepository
```

**Extensions enabled:**
- `phpstan-symfony` - Symfony-specific checks (controllers, services, etc.)
- `phpstan-doctrine` - Doctrine ORM checks (repositories, entities, DQL)

**Helper files:**
- `apps/backend/tests/console-application.php` - Symfony console context
- `apps/backend/tests/object-manager.php` - Doctrine entity manager context

---

## Git Pre-Commit Hooks

### Installation

```bash
# Run once after cloning repository
bash scripts/install-git-hooks.sh
```

Output:
```
✓ Git hooks installed successfully!

Pre-commit hook will now run:
  - PHP-CS-Fixer (code style check)
  - PHPStan (static analysis)

To bypass hooks (not recommended): git commit --no-verify
```

### How it works

1. **Detects changed files** - Only checks modified `.php` files in `apps/backend/`
2. **Runs PHP-CS-Fixer** - Checks code style (dry-run, no modifications)
3. **Runs PHPStan** - Performs static analysis
4. **Blocks commit** if any issues found

### Example Output

**✅ Success:**
```bash
Running pre-commit checks...

Changed PHP files:
apps/backend/src/Controller/TaskController.php

Running PHP-CS-Fixer...
✓ PHP-CS-Fixer passed

Running PHPStan...
✓ PHPStan passed

✓ All pre-commit checks passed!
```

**❌ Failure:**
```bash
Running PHP-CS-Fixer...
✗ PHP-CS-Fixer found issues

Run 'make cs-fixer-fix' to auto-fix or 'docker exec backend-php83 vendor/bin/php-cs-fixer fix' manually
```

### Bypassing Hooks

**Not recommended**, but sometimes necessary (e.g., work in progress):

```bash
git commit --no-verify -m "WIP: refactoring in progress"
```

### Uninstalling Hooks

```bash
rm .git/hooks/pre-commit
```

---

## Makefile Commands

All commands run in Docker container `backend-php83`.

### PHP-CS-Fixer

```bash
make cs-fixer-check    # Check code style (dry-run)
make cs-fixer-fix      # Fix code style automatically
```

### PHPStan

```bash
make phpstan           # Run static analysis
make phpstan-baseline  # Generate baseline
```

### Combined

```bash
make quality-check     # Run cs-fixer-check + phpstan
make quality-fix       # Run cs-fixer-fix + phpstan
```

### Full List

Run `make` or `make help` to see all available commands.

---

## Configuration Details

### PHP-CS-Fixer Configuration

**Location:** `apps/backend/.php-cs-fixer.php`

**Key sections:**

```php
// Paths to analyze
$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

// Ruleset
return (new Config())
    ->setRules([
        '@PSR12' => true,           // PSR-12 standard
        '@Symfony' => true,          // Symfony conventions
        'declare_strict_types' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters']
        ],
        // ... 100+ more rules
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Risky rules enabled:**
- `strict_comparison` - Forces `===` instead of `==`
- `declare_strict_types` - Adds `declare(strict_types=1)`

### PHPStan Configuration

**Location:** `apps/backend/phpstan.neon`

**Key sections:**

```neon
includes:
    - vendor/phpstan/phpstan-doctrine/extension.neon
    - vendor/phpstan/phpstan-symfony/extension.neon

parameters:
    level: 5
    paths:
        - src
        - tests
    excludePaths:
        - src/Kernel.php
        - tests/bootstrap.php
    symfony:
        container_xml_path: var/cache/dev/App_KernelDevDebugContainer.xml
    doctrine:
        repositoryClass: App\Repository\BaseRepository
```

**Ignored errors** (use sparingly):
```neon
ignoreErrors:
    - message: '#Call to an undefined method Doctrine\\ORM\\EntityRepository::#'
      path: src/Repository/*
```

---

## Troubleshooting

### PHP-CS-Fixer Issues

#### "PHP-CS-Fixer not found"

```bash
# Reinstall dependencies
docker exec backend-php83 composer install
```

#### "Configuration file not found"

```bash
# Check file exists
ls -la apps/backend/.php-cs-fixer.php

# Run from correct directory
cd apps/backend
docker exec backend-php83 vendor/bin/php-cs-fixer fix
```

#### "Memory limit exceeded"

PHP-CS-Fixer rarely has memory issues, but if it does:

```bash
docker exec backend-php83 php -d memory_limit=512M vendor/bin/php-cs-fixer fix
```

### PHPStan Issues

#### "Memory limit exceeded"

```bash
# Increase memory limit (default is 1G)
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=2G
```

#### "Container XML file not found"

```bash
# Warm up Symfony cache to generate container XML
docker exec backend-php83 php bin/console cache:clear
docker exec backend-php83 php bin/console cache:warmup

# Try again
make phpstan
```

#### "Too many errors"

Generate baseline to ignore existing errors:

```bash
make phpstan-baseline

# This creates phpstan-baseline.neon
# Commit this file to version control
git add apps/backend/phpstan-baseline.neon
git commit -m "Add PHPStan baseline"
```

#### "False positive errors"

Add to `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        - message: '#Your specific error message#'
          path: src/Your/Specific/Path.php
```

### Git Hooks Issues

#### "Docker container not running"

```bash
# Start Docker containers
docker compose up -d

# Verify container is running
docker ps | grep backend-php83
```

#### "Hooks not running"

```bash
# Check hook exists and is executable
ls -la .git/hooks/pre-commit

# Make executable
chmod +x .git/hooks/pre-commit

# Or reinstall
bash scripts/install-git-hooks.sh
```

#### "Hook blocks commit but I need to commit"

**Option 1:** Fix the issues (recommended)
```bash
make cs-fixer-fix
make phpstan
```

**Option 2:** Bypass hook (not recommended)
```bash
git commit --no-verify -m "Your message"
```

---

## Related Documents

- **[Coding Standards](../CODING_STANDARDS.md)** - SOLID principles and code conventions
- **[Development Workflow](DEVELOPMENT_WORKFLOW.md)** - Daily development commands
- **[Testing](testing/TESTING.md)** - Backend and frontend testing
- **[Troubleshooting](TROUBLESHOOTING.md)** - Common issues and solutions

---

**Last Updated:** 2025-11-12
**Maintainer:** Claude Code AI
**Project Phase:** Production-Ready

---

*Quality checks ensure consistent code style and catch bugs early. Run `make quality-check` before pushing!*
