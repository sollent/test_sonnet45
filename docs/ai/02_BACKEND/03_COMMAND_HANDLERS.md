# 🎯 Фаза 2.3: Обработчики Команд - Простая Реализация

> **Для AI**: Быстрое руководство по реализации обработчиков команд. Копируйте шаблоны, адаптируйте под свои нужды.

## 📍 Что Вам Нужно

- ✅ Сервисы завершены ([Шаг 2.2](02_SERVICES.md))
- 📂 Расположение: `backend/src/Service/VoiceAssistant/Command/Handlers/`
- 🎯 Цель: Обработать 5 базовых команд для MVP

---

## 🚀 Быстрый Старт

### Интерфейс Обработчика

**AI**: Создайте это сначала - все обработчики реализуют его.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/Command/Handlers/CommandHandlerInterface.php

namespace App\Service\VoiceAssistant\Command\Handlers;

use App\Entity\VoiceCommand;

interface CommandHandlerInterface
{
    public function handle(VoiceCommand $command): array;
    public function supports(string $action): bool;
}
```

---

## 📝 Шаблоны Обработчиков

### 1. CreateTaskHandler

**AI**: Самая частая команда - создание задач.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/Command/Handlers/CreateTaskHandler.php

namespace App\Service\VoiceAssistant\Command\Handlers;

use App\Entity\VoiceCommand;
use App\Service\TaskService;
use Psr\Log\LoggerInterface;

class CreateTaskHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskService $taskService,
        private LoggerInterface $logger
    ) {}

    public function handle(VoiceCommand $command): array
    {
        $parsed = $command->getParsedCommand();
        $params = $parsed->getParameters();
        $user = $command->getUser();

        // Extract task data
        $title = $params['title'] ?? 'New Task';
        $description = $params['description'] ?? null;
        $dueDate = $this->parseDueDate($params['due_date'] ?? null);
        $priority = $params['priority'] ?? null;
        $tags = $params['tags'] ?? [];

        try {
            // Create task using existing service
            $task = $this->taskService->createTask(
                $user,
                $title,
                $description,
                $dueDate
            );

            // Add tags if provided
            if ($tags) {
                foreach ($tags as $tagName) {
                    $this->taskService->addTagToTask($task, $tagName);
                }
            }

            // Set priority if provided
            if ($priority) {
                $task->setPriority($priority);
                // Save changes
            }

            $this->logger->info('Task created via voice', [
                'task_id' => $task->getId()->toRfc4122(),
                'command_id' => $command->getId()->toRfc4122()
            ]);

            return [
                'success' => true,
                'task_id' => $task->getId()->toRfc4122(),
                'title' => $task->getTitle(),
                'message' => "Задача '{$task->getTitle()}' создана"
            ];

        } catch (\Exception $e) {
            $this->logger->error('Task creation failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Не удалось создать задачу',
                'error' => $e->getMessage()
            ];
        }
    }

    public function supports(string $action): bool
    {
        return $action === 'create_task';
    }

    private function parseDueDate(?string $dateString): ?\DateTimeImmutable
    {
        if (!$dateString) {
            return null;
        }

        // Handle natural language dates
        $dateString = strtolower($dateString);

        if (str_contains($dateString, 'завтра')) {
            return new \DateTimeImmutable('tomorrow');
        }

        if (str_contains($dateString, 'послезавтра')) {
            return new \DateTimeImmutable('+2 days');
        }

        if (str_contains($dateString, 'через неделю')) {
            return new \DateTimeImmutable('+1 week');
        }

        try {
            return new \DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

### 2. CompleteTaskHandler

**AI**: Отметить задачи как выполненные.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/Command/Handlers/CompleteTaskHandler.php

namespace App\Service\VoiceAssistant\Command\Handlers;

use App\Entity\VoiceCommand;
use App\Service\TaskService;
use App\Service\VoiceAssistant\SmartSearchService;
use Psr\Log\LoggerInterface;

class CompleteTaskHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskService $taskService,
        private SmartSearchService $searchService,
        private LoggerInterface $logger
    ) {}

    public function handle(VoiceCommand $command): array
    {
        $parsed = $command->getParsedCommand();
        $params = $parsed->getParameters();
        $user = $command->getUser();

        // Find task by ID or description
        $task = null;

        if (isset($params['task_id'])) {
            $task = $this->taskService->getTask($params['task_id']);
        } elseif (isset($params['search']) || isset($params['task_name'])) {
            $searchQuery = $params['search'] ?? $params['task_name'];
            $task = $this->searchService->findTaskByDescription($searchQuery, $user);
        }

        if (!$task) {
            return [
                'success' => false,
                'message' => 'Задача не найдена',
                'need_clarification' => true,
                'suggestions' => $this->getSuggestions($user)
            ];
        }

        try {
            $this->taskService->completeTask($task);

            return [
                'success' => true,
                'task_id' => $task->getId()->toRfc4122(),
                'title' => $task->getTitle(),
                'message' => "Задача '{$task->getTitle()}' завершена"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Не удалось завершить задачу',
                'error' => $e->getMessage()
            ];
        }
    }

    public function supports(string $action): bool
    {
        return $action === 'complete_task';
    }

    private function getSuggestions($user): array
    {
        // Return incomplete tasks for user to choose
        $tasks = $this->taskService->getUserTasks($user, ['status' => 'active']);

        return array_map(fn($task) => [
            'id' => $task->getId()->toRfc4122(),
            'title' => $task->getTitle()
        ], array_slice($tasks, 0, 5));
    }
}
```

### 3. FilterTasksHandler

**AI**: Поиск и фильтрация задач.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/Command/Handlers/FilterTasksHandler.php

namespace App\Service\VoiceAssistant\Command\Handlers;

use App\Entity\VoiceCommand;
use App\Service\TaskService;
use App\Repository\TaskRepository;
use Psr\Log\LoggerInterface;

class FilterTasksHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskService $taskService,
        private TaskRepository $taskRepository,
        private LoggerInterface $logger
    ) {}

    public function handle(VoiceCommand $command): array
    {
        $parsed = $command->getParsedCommand();
        $filters = $parsed->getParameters()['filters'] ?? [];
        $user = $command->getUser();

        // Build query criteria
        $criteria = $this->buildCriteria($filters, $user);

        try {
            $tasks = $this->taskRepository->createSearchQueryBuilder($criteria)
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();

            return [
                'success' => true,
                'count' => count($tasks),
                'tasks' => $this->formatTasks($tasks),
                'filters_applied' => $filters,
                'message' => "Найдено задач: " . count($tasks)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка поиска задач',
                'error' => $e->getMessage()
            ];
        }
    }

    public function supports(string $action): bool
    {
        return $action === 'filter_tasks';
    }

    private function buildCriteria(array $filters, $user): array
    {
        $criteria = ['user' => $user];

        // Date filters
        if (isset($filters['date_from'])) {
            $criteria['from_date'] = new \DateTimeImmutable($filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $criteria['to_date'] = new \DateTimeImmutable($filters['date_to']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $criteria['status'] = $filters['status'];
        }

        // Priority filter
        if (isset($filters['priority'])) {
            $criteria['priority'] = $filters['priority'];
        }

        // Tags filter
        if (isset($filters['tags'])) {
            $criteria['tags'] = $filters['tags'];
        }

        return $criteria;
    }

    private function formatTasks(array $tasks): array
    {
        return array_map(fn($task) => [
            'id' => $task->getId()->toRfc4122(),
            'title' => $task->getTitle(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format('Y-m-d H:i'),
        ], $tasks);
    }
}
```

### 4. CreateSubtaskHandler (Опционально для MVP)

**AI**: Добавьте это, если есть время, иначе пропустите.

```php
<?php
// File: apps/backend/src/Service/VoiceAssistant/Command/Handlers/CreateSubtaskHandler.php

namespace App\Service\VoiceAssistant\Command\Handlers;

use App\Entity\VoiceCommand;
use App\Service\TaskService;
use App\Service\VoiceAssistant\SmartSearchService;
use Psr\Log\LoggerInterface;

class CreateSubtaskHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskService $taskService,
        private SmartSearchService $searchService,
        private LoggerInterface $logger
    ) {}

    public function handle(VoiceCommand $command): array
    {
        $parsed = $command->getParsedCommand();
        $params = $parsed->getParameters();
        $user = $command->getUser();

        // Find parent task
        $parentTask = null;

        if (isset($params['parent_task_id'])) {
            $parentTask = $this->taskService->getTask($params['parent_task_id']);
        } elseif (isset($params['parent_task_name'])) {
            $parentTask = $this->searchService->findTaskByDescription(
                $params['parent_task_name'],
                $user
            );
        }

        if (!$parentTask) {
            return [
                'success' => false,
                'message' => 'Родительская задача не найдена'
            ];
        }

        try {
            // Create subtask
            $subtask = $this->taskService->createSubtask(
                $parentTask,
                $params['title'] ?? 'New Subtask',
                $params['description'] ?? null
            );

            return [
                'success' => true,
                'subtask_id' => $subtask->getId()->toRfc4122(),
                'parent_id' => $parentTask->getId()->toRfc4122(),
                'message' => "Подзадача создана для '{$parentTask->getTitle()}'"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Не удалось создать подзадачу',
                'error' => $e->getMessage()
            ];
        }
    }

    public function supports(string $action): bool
    {
        return $action === 'create_subtask';
    }
}
```

---

## 🔧 Конфигурация Обработчиков

**AI**: Зарегистрируйте обработчики в `backend/config/services.yaml`:

```yaml
services:
    # Command Handlers
    App\Service\VoiceAssistant\Command\Handlers\CreateTaskHandler:
        tags: ['voice.command_handler']

    App\Service\VoiceAssistant\Command\Handlers\CompleteTaskHandler:
        tags: ['voice.command_handler']

    App\Service\VoiceAssistant\Command\Handlers\FilterTasksHandler:
        tags: ['voice.command_handler']

    App\Service\VoiceAssistant\Command\Handlers\CreateSubtaskHandler:
        tags: ['voice.command_handler']

    # Make handlers available via locator
    voice.handler.create_task:
        alias: App\Service\VoiceAssistant\Command\Handlers\CreateTaskHandler

    voice.handler.complete_task:
        alias: App\Service\VoiceAssistant\Command\Handlers\CompleteTaskHandler

    voice.handler.filter_tasks:
        alias: App\Service\VoiceAssistant\Command\Handlers\FilterTasksHandler

    voice.handler.create_subtask:
        alias: App\Service\VoiceAssistant\Command\Handlers\CreateSubtaskHandler
```

---

## ✅ Быстрый Тест

**AI**: Создайте тестовый файл для проверки работы обработчиков:

```php
<?php
// File: apps/backend/tests/CommandHandlersTest.php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\VoiceAssistant\Command\Handlers\CreateTaskHandler;
use App\Entity\VoiceCommand;
use App\Entity\User;
use App\ValueObject\CommandType;
use App\ValueObject\ParsedCommand;

class CommandHandlersTest extends KernelTestCase
{
    public function testCreateTaskHandler(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $handler = $container->get(CreateTaskHandler::class);
        $this->assertTrue($handler->supports('create_task'));

        // Test with mock command (implement when you have test user)
        $this->assertTrue(true);
    }
}
```

---

## 🎯 Приоритет Реализации для MVP

**AI**: Реализуйте в таком порядке:

1. ✅ **CreateTaskHandler** - Самый важный (90% команд)
2. ✅ **CompleteTaskHandler** - Второй по частоте использования
3. ⚠️ **FilterTasksHandler** - Желательно иметь
4. ⏭️ **CreateSubtaskHandler** - Пропустить для начального MVP

---

## 🚨 Общие Шаблоны

### Шаблон 1: Задача Не Найдена - Спросить Пользователя

```php
if (!$task) {
    return [
        'success' => false,
        'need_clarification' => true,
        'message' => 'Не могу найти задачу. Уточните, пожалуйста.',
        'suggestions' => $this->getSimilarTasks($searchQuery, $user)
    ];
}
```

### Шаблон 2: Обработка Ошибок

```php
try {
    // Do something
    return ['success' => true, 'message' => 'OK'];
} catch (\Exception $e) {
    $this->logger->error('Handler failed', ['error' => $e->getMessage()]);
    return ['success' => false, 'message' => 'Ошибка', 'error' => $e->getMessage()];
}
```

### Шаблон 3: Даты на Естественном Языке

```php
private function parseDate(string $input): ?\DateTimeImmutable
{
    $map = [
        'сегодня' => 'today',
        'завтра' => 'tomorrow',
        'послезавтра' => '+2 days',
        'через неделю' => '+1 week',
        'через месяц' => '+1 month'
    ];

    foreach ($map as $ru => $en) {
        if (str_contains(strtolower($input), $ru)) {
            return new \DateTimeImmutable($en);
        }
    }

    return null;
}
```

---

## 📊 Поток Обработчика

```
VoiceCommand
     ↓
CommandExecutorService
     ↓
getHandler($action) → CreateTaskHandler
     ↓
handle(VoiceCommand)
     ↓
Извлечь параметры
     ↓
Вызвать TaskService
     ↓
Вернуть результат
```

---

## ✅ Чеклист Реализации для AI

- [ ] Создан `CommandHandlerInterface.php`
- [ ] Реализован `CreateTaskHandler.php`
- [ ] Реализован `CompleteTaskHandler.php`
- [ ] (Опционально) Реализован `FilterTasksHandler.php`
- [ ] Добавлены сервисы в `services.yaml`
- [ ] Протестирован хотя бы CreateTaskHandler

---

## 🎯 Следующий Шаг

**Обработчики готовы!** Теперь создайте API эндпоинты:

→ [API Эндпоинты](04_API_ENDPOINTS.md) - Создать REST API для frontend

---

**Советы для AI**:
- Начните только с CreateTaskHandler
- Используйте существующие методы TaskService
- Не создавайте новые операции с базой данных
- Обработка ошибок простая: try/catch + return array
- Разбор естественного языка базовый для MVP

**Время**: 1-2 часа для MVP обработчиков
