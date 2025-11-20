# 🔧 Рефакторинг Voice Commands - Документация по миграции

> **Версия**: 1.0
> **Дата**: 2025-11-20
> **Статус**: В процессе миграции

## 📊 Результаты рефакторинга

### До рефакторинга (God Object)
- **VoiceCommandExecutor.php**: 1820 строк кода
- **Методов**: 28 приватных методов в одном классе
- **SOLID**: Нарушены все принципы
- **Тестируемость**: Невозможно изолированное тестирование
- **Добавление команды**: 3 изменения в разных местах

### После рефакторинга (Command Pattern)
- **VoiceCommandExecutorNew.php**: 170 строк кода
- **Команды**: Отдельный класс для каждой команды (~100 строк)
- **SOLID**: Полное соответствие всем принципам
- **Тестируемость**: Легкое unit-тестирование каждой команды
- **Добавление команды**: 1 новый класс + автоматическая регистрация

## 🏗️ Новая архитектура

```
apps/backend/src/Service/AI/
├── Command/                           # Команды (Command Pattern)
│   ├── Contract/
│   │   └── VoiceCommandInterface.php  # Интерфейс команды
│   ├── Base/
│   │   ├── AbstractVoiceCommand.php   # Базовая логика
│   │   └── AbstractBatchCommand.php   # Для batch операций
│   ├── Task/                          # Команды задач
│   │   ├── CreateTaskCommand.php
│   │   ├── CompleteTaskCommand.php
│   │   └── UpdateTaskCommand.php
│   └── Batch/                         # Batch команды
│       └── BulkCompleteCommand.php
├── Registry/
│   └── CommandRegistry.php            # Реестр команд
├── Service/                           # Вспомогательные сервисы
│   ├── TaskFinder.php                # Поиск задач
│   ├── DateTimeResolver.php          # Парсинг дат
│   ├── PriorityMapper.php            # Маппинг приоритетов
│   └── StatusMapper.php              # Маппинг статусов
├── Response/
│   ├── CommandResponse.php           # DTO для ответов
│   └── ResponseBuilder.php           # Построитель ответов
└── VoiceCommandExecutorNew.php       # Новый executor (170 строк!)
```

## 🚀 Как добавить новую команду

### Шаг 1: Создать класс команды

```php
<?php

namespace App\Service\AI\Command\Task;

use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\Response\CommandResponse;
use App\ValueObject\ParsedCommand;

class MyNewCommand extends AbstractVoiceCommand
{
    public function getAction(): string
    {
        return ParsedCommand::ACTION_MY_NEW_ACTION;
    }

    protected function validateParameters(array $parameters): void
    {
        // Валидация параметров
        if (empty($parameters['required_field'])) {
            throw new RuntimeException('Required field is missing');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        // Логика команды
        $result = $this->doSomething($parameters);

        // Возврат типизированного ответа
        return CommandResponse::success(
            'my_action_completed',
            'Действие выполнено успешно',
            ['result' => $result]
        );
    }
}
```

### Шаг 2: Добавить константу в ParsedCommand

```php
// apps/backend/src/ValueObject/ParsedCommand.php
public const ACTION_MY_NEW_ACTION = 'my_new_action';

// И добавить в массив isValidAction()
```

### Шаг 3: Всё! 🎉

Команда автоматически зарегистрируется через DI тег `voice.command`.

## 📝 Примеры миграции старых команд

### Было (в VoiceCommandExecutor):

```php
private function executeCompleteTask(array $parameters, User $user): array
{
    $search = $parameters['search'] ?? $parameters['title'] ?? null;

    if (empty($search)) {
        throw new RuntimeException('Search query is required');
    }

    $task = $this->searchService->findBestMatch($search, $user);

    if (!$task) {
        return [
            'type'    => 'task_not_found',
            'success' => false,
            'message' => sprintf('Задача "%s" не найдена', $search),
        ];
    }

    $task = $this->taskService->completeTask($task, $user);

    return [
        'type'    => 'task_completed',
        'success' => true,
        'message' => sprintf('Задача "%s" отмечена как выполненная', $task->getTitle()),
        'task'    => ['id' => $task->getId(), 'title' => $task->getTitle()],
    ];
}
```

### Стало (отдельная команда):

```php
class CompleteTaskCommand extends AbstractVoiceCommand
{
    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return $this->responseBuilder->taskNotFound($search);
        }

        $task = $this->taskService->completeTask($task, $user);
        return $this->responseBuilder->taskCompleted($task);
    }
}
```

## 🧪 Тестирование

### Старый подход (сложно)
```php
// Невозможно тестировать приватные методы
// Нужно мокать весь VoiceCommandExecutor
// Сложно изолировать логику
```

### Новый подход (легко)
```php
class CompleteTaskCommandTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        // Arrange
        $task = $this->createMock(Task::class);
        $this->taskFinder->method('find')->willReturn($task);

        // Act
        $response = $this->command->execute($parameters, $this->user);

        // Assert
        $this->assertTrue($response->isSuccess());
    }
}
```

## 🔄 Статус миграции команд

| Команда | Старый метод | Новый класс | Статус |
|---------|--------------|-------------|---------|
| create_task | executeCreateTask | CreateTaskCommand | ✅ Мигрирован |
| complete_task | executeCompleteTask | CompleteTaskCommand | ✅ Мигрирован |
| update_task | executeUpdateTask | UpdateTaskCommand | ✅ Мигрирован |
| bulk_complete | executeBulkComplete | BulkCompleteCommand | ✅ Мигрирован |
| uncomplete_task | executeUncompleteTask | - | ⏳ Ожидает |
| filter_tasks | executeFilterTasks | - | ⏳ Ожидает |
| create_subtask | executeCreateSubtask | - | ⏳ Ожидает |
| delete_task | executeDeleteTask | - | ⏳ Ожидает |
| ... | ... | ... | ... |

## 🛠️ Вспомогательные сервисы

### TaskFinder
- `find()` - найти задачу
- `findOrFail()` - найти или исключение
- `findMultiple()` - найти несколько
- `filter()` - фильтрация

### DateTimeResolver
- `resolveDateRange()` - парсинг диапазона дат
- `resolveDate()` - парсинг одной даты
- `resolvePeriod()` - парсинг периода

### PriorityMapper / StatusMapper
- `map()` - преобразование текста в enum
- `getSupportedValues()` - список значений

### ResponseBuilder
- `taskCreated()` - ответ о создании
- `taskCompleted()` - ответ о завершении
- `taskNotFound()` - ответ "не найдено"
- `batchSuccess()` - ответ batch операции

## 📋 Чеклист для полной миграции

- [x] Создать базовую инфраструктуру (интерфейсы, базовые классы)
- [x] Выделить вспомогательные сервисы
- [x] Мигрировать простые команды (create, complete, update)
- [x] Мигрировать batch команды (bulk_complete)
- [x] Настроить DI и автоматическую регистрацию
- [x] Создать новый VoiceCommandExecutor
- [ ] Мигрировать оставшиеся команды (22 из 26)
- [ ] Переключить контроллер на новый executor
- [ ] Удалить старый VoiceCommandExecutor
- [ ] Написать полное покрытие тестами

## 🎯 Преимущества новой архитектуры

1. **SOLID принципы**
   - ✅ Single Responsibility: каждая команда - одна ответственность
   - ✅ Open/Closed: добавление команд без изменения кода
   - ✅ Liskov Substitution: все команды взаимозаменяемы
   - ✅ Interface Segregation: маленькие интерфейсы
   - ✅ Dependency Inversion: зависимости от абстракций

2. **Тестируемость**
   - Unit тесты для каждой команды
   - Легкое мокирование зависимостей
   - Изолированное тестирование логики

3. **Поддерживаемость**
   - Код разбит на маленькие классы
   - Четкая структура директорий
   - Переиспользование через базовые классы

4. **Масштабируемость**
   - Легко добавлять новые команды
   - Автоматическая регистрация через DI
   - Нет ограничений на количество команд

## 📚 Дополнительные ресурсы

- [Command Pattern - Gang of Four](https://refactoring.guru/design-patterns/command)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Symfony DI Tags](https://symfony.com/doc/current/service_container/tags.html)
- [PHPUnit Mocking](https://phpunit.de/manual/current/en/test-doubles.html)