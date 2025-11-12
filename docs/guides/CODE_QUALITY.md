# 🎯 Инструменты контроля качества кода - PHP-CS-Fixer и PHPStan

> **TL;DR**: Автоматизированные инструменты контроля качества кода для backend: PHP-CS-Fixer (стиль кода) и PHPStan (статический анализ). Настроены для PHP 8.3 с поддержкой Symfony и Doctrine. Git-хуки запускают проверки автоматически перед коммитами.

---

## 📋 Содержание

- [Обзор](#обзор)
- [PHP-CS-Fixer (Стиль кода)](#php-cs-fixer-стиль-кода)
- [PHPStan (Статический анализ)](#phpstan-статический-анализ)
- [Git Pre-Commit хуки](#git-pre-commit-хуки)
- [Команды Makefile](#команды-makefile)
- [Детали конфигурации](#детали-конфигурации)
- [Решение проблем](#решение-проблем)

---

## Обзор

В этом проекте используются два основных инструмента контроля качества кода:

1. **PHP-CS-Fixer** - Автоматически исправляет проблемы стиля кода (PSR-12 + современный PHP 8.3)
2. **PHPStan** - Инструмент статического анализа, который находит баги без запуска кода (уровень 5)

Оба инструмента:
- ✅ Интегрированы в процесс разработки через Makefile
- ✅ Запускаются автоматически через Git pre-commit хуки
- ✅ Настроены для Symfony + Doctrine
- ✅ Оптимизированы для возможностей PHP 8.3

---

## PHP-CS-Fixer (Стиль кода)

### Что делает

PHP-CS-Fixer автоматически форматирует PHP код в соответствии с:
- Стандартом **PSR-12** (стандарт кодирования PHP-FIG)
- Соглашениями **Symfony**
- Возможностями **современного PHP 8.3** (strict types, readonly, enums, match)

### Быстрое использование

```bash
# Проверить стиль кода (dry-run, показывает что будет исправлено)
make cs-fixer-check

# Автоматически исправить стиль кода
make cs-fixer-fix
```

### Детальное использование

```bash
# Проверить все файлы (dry-run)
docker exec backend-php83 vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# Проверить конкретную директорию
docker exec backend-php83 vendor/bin/php-cs-fixer fix src/Controller --dry-run --diff

# Исправить все файлы
docker exec backend-php83 vendor/bin/php-cs-fixer fix --verbose

# Исправить конкретный файл
docker exec backend-php83 vendor/bin/php-cs-fixer fix src/Controller/TaskController.php
```

### Что исправляется

**До:**
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

**После:**
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

### Применяемые ключевые правила

- ✅ `declare(strict_types=1)` во всех файлах
- ✅ Короткий синтаксис массивов `[]` вместо `array()`
- ✅ Завершающие запятые в многострочных массивах/аргументах
- ✅ Выравнивание бинарных операторов (`=>`)
- ✅ Упорядоченные элементы класса (свойства → конструктор → методы)
- ✅ Форматирование и выравнивание PHPDoc
- ✅ Отсутствие неиспользуемых импортов
- ✅ Правильные отступы и пробелы

### Конфигурация

**Файл:** `apps/backend/.php-cs-fixer.php`

```php
return (new Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        // ... 100+ правил
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Анализируемые пути:**
- `apps/backend/src/`
- `apps/backend/tests/`

---

## PHPStan (Статический анализ)

### Что делает

PHPStan выполняет статический анализ кода для поиска:
- Несоответствий типов
- Неопределенных переменных/методов
- Мертвого кода
- Логических ошибок
- Отсутствующих типов возврата
- Разыменования null-указателей

### Быстрое использование

```bash
# Запустить анализ
make phpstan

# Сгенерировать baseline (игнорировать существующие ошибки)
make phpstan-baseline
```

### Детальное использование

```bash
# Запустить полный анализ
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=1G

# Проанализировать конкретный путь
docker exec backend-php83 vendor/bin/phpstan analyse src/Controller

# Очистить кэш результатов
docker exec backend-php83 vendor/bin/phpstan clear-result-cache

# Сгенерировать baseline файл
docker exec backend-php83 vendor/bin/phpstan analyse --generate-baseline
```

### Уровни анализа

PHPStan имеет 10 уровней (0-9), каждый прогрессивно строже:

| Уровень | Описание                                    | Текущий |
|---------|---------------------------------------------|---------|
| 0       | Базовые проверки                            |         |
| 1       | Неизвестные классы                          |         |
| 2       | Неизвестные методы                          |         |
| 3       | Неизвестные свойства/возвраты методов       |         |
| 4       | Базовый мертвый код                         |         |
| **5**   | **Аргументы/типы возврата**                 | ✅ Да   |
| 6       | Отсутствующие подсказки типов               |         |
| 7       | Объединения и nullable типы                 |         |
| 8       | Вызов методов на nullable типах             |         |
| 9       | Mixed типы не разрешены                     |         |

**Текущий уровень:** 5 (хороший баланс между строгостью и практичностью)

### Примеры обнаруженных ошибок

**Несоответствие типов:**
```php
// ❌ Ошибка PHPStan
public function setStatus(string $status): void
{
    $this->status = 123; // Ожидается string, получен int
}
```

**Неопределенный метод:**
```php
// ❌ Ошибка PHPStan
$task = $taskRepository->find($id);
$task->getName(); // Метод getName() не найден в Task|null
```

**Отсутствующий тип возврата:**
```php
// ❌ Ошибка PHPStan (уровень 5+)
public function calculate($a, $b) // Отсутствует тип возврата
{
    return $a + $b;
}
```

### Конфигурация

**Файл:** `apps/backend/phpstan.neon`

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

**Включенные расширения:**
- `phpstan-symfony` - Проверки специфичные для Symfony (контроллеры, сервисы и т.д.)
- `phpstan-doctrine` - Проверки Doctrine ORM (репозитории, сущности, DQL)

**Вспомогательные файлы:**
- `apps/backend/tests/console-application.php` - Контекст консоли Symfony
- `apps/backend/tests/object-manager.php` - Контекст менеджера сущностей Doctrine

---

## Git Pre-Commit хуки

### Установка

```bash
# Запустить один раз после клонирования репозитория
bash scripts/install-git-hooks.sh
```

Вывод:
```
✓ Git hooks installed successfully!

Pre-commit hook will now run:
  - PHP-CS-Fixer (code style check)
  - PHPStan (static analysis)

To bypass hooks (not recommended): git commit --no-verify
```

### Как это работает

1. **Определяет измененные файлы** - Проверяет только измененные `.php` файлы в `apps/backend/`
2. **Запускает PHP-CS-Fixer** - Проверяет стиль кода (dry-run, без изменений)
3. **Запускает PHPStan** - Выполняет статический анализ
4. **Блокирует коммит** если найдены проблемы

### Пример вывода

**✅ Успех:**
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

**❌ Ошибка:**
```bash
Running PHP-CS-Fixer...
✗ PHP-CS-Fixer found issues

Run 'make cs-fixer-fix' to auto-fix or 'docker exec backend-php83 vendor/bin/php-cs-fixer fix' manually
```

### Обход хуков

**Не рекомендуется**, но иногда необходимо (например, незавершенная работа):

```bash
git commit --no-verify -m "WIP: refactoring in progress"
```

### Удаление хуков

```bash
rm .git/hooks/pre-commit
```

---

## Команды Makefile

Все команды выполняются в Docker-контейнере `backend-php83`.

### PHP-CS-Fixer

```bash
make cs-fixer-check    # Проверить стиль кода (dry-run)
make cs-fixer-fix      # Автоматически исправить стиль кода
```

### PHPStan

```bash
make phpstan           # Запустить статический анализ
make phpstan-baseline  # Сгенерировать baseline
```

### Комбинированные

```bash
make quality-check     # Запустить cs-fixer-check + phpstan
make quality-fix       # Запустить cs-fixer-fix + phpstan
```

### Полный список

Запустите `make` или `make help` чтобы увидеть все доступные команды.

---

## Детали конфигурации

### Конфигурация PHP-CS-Fixer

**Расположение:** `apps/backend/.php-cs-fixer.php`

**Ключевые секции:**

```php
// Пути для анализа
$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

// Набор правил
return (new Config())
    ->setRules([
        '@PSR12' => true,           // Стандарт PSR-12
        '@Symfony' => true,          // Соглашения Symfony
        'declare_strict_types' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters']
        ],
        // ... еще 100+ правил
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Включенные рискованные правила:**
- `strict_comparison` - Принудительно `===` вместо `==`
- `declare_strict_types` - Добавляет `declare(strict_types=1)`

### Конфигурация PHPStan

**Расположение:** `apps/backend/phpstan.neon`

**Ключевые секции:**

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

**Игнорируемые ошибки** (использовать экономно):
```neon
ignoreErrors:
    - message: '#Call to an undefined method Doctrine\\ORM\\EntityRepository::#'
      path: src/Repository/*
```

---

## Решение проблем

### Проблемы PHP-CS-Fixer

#### "PHP-CS-Fixer не найден"

```bash
# Переустановить зависимости
docker exec backend-php83 composer install
```

#### "Файл конфигурации не найден"

```bash
# Проверить существование файла
ls -la apps/backend/.php-cs-fixer.php

# Запустить из правильной директории
cd apps/backend
docker exec backend-php83 vendor/bin/php-cs-fixer fix
```

#### "Превышен лимит памяти"

PHP-CS-Fixer редко имеет проблемы с памятью, но если возникают:

```bash
docker exec backend-php83 php -d memory_limit=512M vendor/bin/php-cs-fixer fix
```

### Проблемы PHPStan

#### "Превышен лимит памяти"

```bash
# Увеличить лимит памяти (по умолчанию 1G)
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=2G
```

#### "XML файл контейнера не найден"

```bash
# Прогреть кэш Symfony для генерации XML контейнера
docker exec backend-php83 php bin/console cache:clear
docker exec backend-php83 php bin/console cache:warmup

# Попробовать снова
make phpstan
```

#### "Слишком много ошибок"

Сгенерировать baseline для игнорирования существующих ошибок:

```bash
make phpstan-baseline

# Это создаст phpstan-baseline.neon
# Закоммитить этот файл в систему контроля версий
git add apps/backend/phpstan-baseline.neon
git commit -m "Add PHPStan baseline"
```

#### "Ложноположительные ошибки"

Добавить в `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        - message: '#Your specific error message#'
          path: src/Your/Specific/Path.php
```

### Проблемы Git хуков

#### "Docker контейнер не запущен"

```bash
# Запустить Docker контейнеры
docker compose up -d

# Проверить что контейнер запущен
docker ps | grep backend-php83
```

#### "Хуки не выполняются"

```bash
# Проверить что хук существует и исполняемый
ls -la .git/hooks/pre-commit

# Сделать исполняемым
chmod +x .git/hooks/pre-commit

# Или переустановить
bash scripts/install-git-hooks.sh
```

#### "Хук блокирует коммит, но мне нужно закоммитить"

**Вариант 1:** Исправить проблемы (рекомендуется)
```bash
make cs-fixer-fix
make phpstan
```

**Вариант 2:** Обойти хук (не рекомендуется)
```bash
git commit --no-verify -m "Your message"
```

---

## Связанные документы

- **[Стандарты кодирования](../CODING_STANDARDS.md)** - SOLID принципы и соглашения кода
- **[Рабочий процесс разработки](DEVELOPMENT_WORKFLOW.md)** - Ежедневные команды разработки
- **[Тестирование](testing/TESTING.md)** - Тестирование backend и frontend
- **[Решение проблем](TROUBLESHOOTING.md)** - Распространенные проблемы и решения

---

**Последнее обновление:** 2025-11-12
**Сопровождающий:** Claude Code AI
**Фаза проекта:** Production-Ready

---

*Проверки качества обеспечивают единообразный стиль кода и раннее обнаружение багов. Запускайте `make quality-check` перед отправкой изменений!*
