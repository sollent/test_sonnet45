# 📡 План реализации Real-time обновлений через Centrifugo

## 📋 Оглавление
1. [Обзор проекта](#обзор-проекта)
2. [Архитектура решения](#архитектура-решения)
3. [Фаза 1: Backend - Миграция на phpcent](#фаза-1-backend---миграция-на-phpcent)
4. [Фаза 2: Интеграция событий](#фаза-2-интеграция-событий)
5. [Фаза 3: JWT аутентификация](#фаза-3-jwt-аутентификация)
6. [Фаза 4: Frontend интеграция](#фаза-4-frontend-интеграция)
7. [Фаза 5: Статистика](#фаза-5-статистика)
8. [Фаза 6: Production настройка](#фаза-6-production-настройка)
9. [Тестирование](#тестирование)
10. [Чеклист выполнения](#чеклист-выполнения)

---

## Обзор проекта

### Цель
Интеграция WebSocket соединений через Centrifugo для real-time обновлений в системе управления задачами.

### Технологии
- **Backend**: Symfony 7.1, PHP 8.3
- **Frontend**: Vue.js 3.4, TypeScript
- **WebSocket**: Centrifugo v5
- **Библиотеки**: phpcent (backend), centrifuge-js (frontend)

### Время выполнения
- **Общее**: 10-14 часов
- **По фазам**: каждая фаза независима и может выполняться отдельно

---

## Архитектура решения

### Поток данных
```
Voice Command → VoiceProcessingService → CommandExecutor
                          ↓
                  CommandEventMapper
                          ↓
                  WebSocketPublisher (phpcent)
                          ↓
                     Centrifugo
                          ↓
                  Frontend (centrifuge-js)
                          ↓
                  GlassmorphismToast + UI Update
```

### Каналы WebSocket
- `personal:{userId}` - основной канал пользователя
- `personal:{userId}:voice` - события голосовых команд
- `personal:{userId}:tasks` - события задач

### События для публикации
События определяются через константы `ParsedCommand::ACTION_*` для type safety.

---

## Фаза 1: Backend - Миграция на phpcent

### Шаг 1.1: Установка phpcent

```bash
# Выполнить в контейнере backend
docker exec backend-php83 composer require centrifugal/phpcent
```

### Шаг 1.2: Создание CommandEventMapper

**Создать файл:** `apps/backend/src/Service/AI/Service/CommandEventMapper.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\AI\Service;

use App\ValueObject\ParsedCommand;

/**
 * Маппер для определения какие команды требуют WebSocket событий
 * Использует константы из ParsedCommand для type safety
 */
class CommandEventMapper
{
    /**
     * Конфигурация WebSocket событий для каждой команды
     * Ключи - константы ACTION_* из ParsedCommand
     */
    private const COMMAND_EVENTS = [
        // ===== КОМАНДЫ СОЗДАНИЯ =====
        ParsedCommand::ACTION_CREATE_TASK => [
            'publish' => true,
            'event' => 'task.created',
            'includeStats' => true,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_CREATE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.created',
            'batch' => true,
            'includeStats' => true,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_CREATE_SUBTASK => [
            'publish' => true,
            'event' => 'subtask.created',
            'includeStats' => false,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_CREATE_MULTIPLE_SUBTASKS => [
            'publish' => true,
            'event' => 'subtasks.created',
            'batch' => true,
            'includeStats' => false,
            'includeEntity' => true
        ],

        // ===== КОМАНДЫ ИЗМЕНЕНИЯ СОСТОЯНИЯ =====
        ParsedCommand::ACTION_UPDATE_TASK => [
            'publish' => true,
            'event' => 'task.updated',
            'includeStats' => false,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_COMPLETE_TASK => [
            'publish' => true,
            'event' => 'task.completed',
            'includeStats' => true,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_COMPLETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.completed',
            'batch' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_COMPLETE_SUBTASKS => [
            'publish' => true,
            'event' => 'subtasks.completed',
            'batch' => true,
            'includeStats' => false,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_UNCOMPLETE_TASK => [
            'publish' => true,
            'event' => 'task.uncompleted',
            'includeStats' => true,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_UNCOMPLETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.uncompleted',
            'batch' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],

        // ===== КОМАНДЫ С ТЕГАМИ =====
        ParsedCommand::ACTION_ADD_TAG => [
            'publish' => true,
            'event' => 'task.tag_added',
            'includeStats' => false,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_REMOVE_TAG => [
            'publish' => true,
            'event' => 'task.tag_removed',
            'includeStats' => false,
            'includeEntity' => true
        ],

        // ===== КОМАНДЫ МОДИФИКАЦИИ =====
        ParsedCommand::ACTION_SET_DESCRIPTION => [
            'publish' => true,
            'event' => 'task.description_updated',
            'includeStats' => false,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_MOVE_TASK => [
            'publish' => true,
            'event' => 'task.moved',
            'includeStats' => false,
            'includeEntity' => true
        ],
        ParsedCommand::ACTION_DUPLICATE_TASK => [
            'publish' => true,
            'event' => 'task.duplicated',
            'includeStats' => true,
            'includeEntity' => true
        ],

        // ===== КОМАНДЫ УДАЛЕНИЯ =====
        ParsedCommand::ACTION_DELETE_TASK => [
            'publish' => true,
            'event' => 'task.deleted',
            'includeStats' => true,
            'includeEntity' => false // Сущность уже удалена
        ],
        ParsedCommand::ACTION_DELETE_MULTIPLE_TASKS => [
            'publish' => true,
            'event' => 'tasks.deleted',
            'batch' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],

        // ===== МАССОВЫЕ ОПЕРАЦИИ (С ПРОГРЕССОМ) =====
        ParsedCommand::ACTION_BULK_COMPLETE => [
            'publish' => true,
            'event' => 'tasks.bulk_completed',
            'progress' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_BULK_UPDATE => [
            'publish' => true,
            'event' => 'tasks.bulk_updated',
            'progress' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_BULK_DELETE => [
            'publish' => true,
            'event' => 'tasks.bulk_deleted',
            'progress' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_BULK_MOVE => [
            'publish' => true,
            'event' => 'tasks.bulk_moved',
            'progress' => true,
            'includeStats' => false,
            'includeEntity' => false
        ],
        ParsedCommand::ACTION_CLEANUP_COMPLETED => [
            'publish' => true,
            'event' => 'tasks.cleanup_completed',
            'progress' => true,
            'includeStats' => true,
            'includeEntity' => false
        ],

        // ===== КОМАНДЫ КОНВЕРТАЦИИ =====
        ParsedCommand::ACTION_CONVERT_SUBTASK_TO_TASK => [
            'publish' => true,
            'event' => 'subtask.converted',
            'includeStats' => true,
            'includeEntity' => true
        ],

        // ===== НЕ ПУБЛИКУЕМЫЕ КОМАНДЫ =====
        ParsedCommand::ACTION_FILTER_TASKS => [
            'publish' => false // Read-only операция
        ],
        ParsedCommand::ACTION_CLARIFICATION_NEEDED => [
            'publish' => false // Служебная команда
        ],
        ParsedCommand::ACTION_UNKNOWN => [
            'publish' => false // Неизвестная команда
        ],
    ];

    public function shouldPublish(string $action): bool
    {
        return self::COMMAND_EVENTS[$action]['publish'] ?? false;
    }

    public function getEventName(string $action): ?string
    {
        if (!$this->shouldPublish($action)) {
            return null;
        }
        return self::COMMAND_EVENTS[$action]['event'];
    }

    public function requiresProgress(string $action): bool
    {
        return self::COMMAND_EVENTS[$action]['progress'] ?? false;
    }

    public function isBatchOperation(string $action): bool
    {
        return self::COMMAND_EVENTS[$action]['batch'] ?? false;
    }

    public function shouldIncludeStats(string $action): bool
    {
        return self::COMMAND_EVENTS[$action]['includeStats'] ?? false;
    }

    public function shouldIncludeEntity(string $action): bool
    {
        return self::COMMAND_EVENTS[$action]['includeEntity'] ?? false;
    }

    /**
     * Получить конфигурацию для действия
     */
    public function getConfig(string $action): array
    {
        return self::COMMAND_EVENTS[$action] ?? ['publish' => false];
    }
}
```

### Шаг 1.3: Обновление WebSocketPublisher

**Обновить файл:** `apps/backend/src/Service/AI/WebSocketPublisher.php`

Добавить в начало:
```php
use phpcent\Client;
```

Заменить конструктор и методы:

```php
private Client $centrifugo;
private bool $enabled;

public function __construct(
    string $centrifugoUrl,
    string $centrifugoApiKey,
    bool $websocketEnabled,
    private readonly LoggerInterface $logger
) {
    $this->enabled = $websocketEnabled;

    if ($this->enabled) {
        $this->centrifugo = new Client($centrifugoUrl);
        $this->centrifugo->setApiKey($centrifugoApiKey);
    }
}

/**
 * Публикация события в персональный канал пользователя
 */
public function publish(int $userId, string $event, array $data): bool
{
    if (!$this->enabled) {
        return false;
    }

    $channel = "personal:{$userId}";

    $message = [
        'event' => $event,
        'data' => $data,
        'metadata' => [
            'timestamp' => time(),
            'user_id' => $userId,
            'version' => '1.0'
        ]
    ];

    try {
        $result = $this->centrifugo->publish($channel, $message);

        $this->logger->info('Published event to Centrifugo', [
            'channel' => $channel,
            'event' => $event,
            'success' => $result !== false
        ]);

        return $result !== false;
    } catch (\Exception $e) {
        $this->logger->error('Failed to publish to Centrifugo', [
            'error' => $e->getMessage(),
            'channel' => $channel,
            'event' => $event
        ]);
        return false;
    }
}

/**
 * Публикация события с прогрессом
 */
public function publishProgress(
    int $userId,
    string $commandId,
    int $current,
    int $total,
    string $message
): bool {
    $percent = $total > 0 ? round(($current / $total) * 100) : 0;

    return $this->publish($userId, 'progress.update', [
        'command_id' => $commandId,
        'progress' => [
            'current' => $current,
            'total' => $total,
            'percent' => $percent,
            'message' => $message
        ]
    ]);
}
```

---

## Фаза 2: Интеграция событий

### Шаг 2.1: Создание TaskStatsCollector

**Создать файл:** `apps/backend/src/Service/TaskStatsCollector.php`

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\TaskStatus;
use App\Repository\Database\TagRepository;
use App\Repository\Database\TaskRepository;

/**
 * Сервис сбора статистики по задачам пользователя
 */
class TaskStatsCollector
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository
    ) {
    }

    /**
     * Собрать полную статистику для пользователя
     */
    public function collectForUser(User $user): array
    {
        return [
            'tasks_total' => $this->taskRepository->countByUser($user),
            'tasks_pending' => $this->taskRepository->countByStatus($user, TaskStatus::PENDING),
            'tasks_in_progress' => $this->taskRepository->countByStatus($user, TaskStatus::IN_PROGRESS),
            'tasks_completed' => $this->taskRepository->countByStatus($user, TaskStatus::COMPLETED),
            'completed_today' => $this->taskRepository->countCompletedToday($user),
            'due_soon' => $this->taskRepository->countDueSoon($user, 3),
            'overdue' => $this->taskRepository->countOverdue($user),
            'tags_used' => $this->tagRepository->countByUser($user),
            'completion_rate' => $this->calculateCompletionRate($user)
        ];
    }

    private function calculateCompletionRate(User $user): float
    {
        $total = $this->taskRepository->countByUser($user);
        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->taskRepository->countByStatus($user, TaskStatus::COMPLETED);
        return round(($completed / $total) * 100, 1);
    }
}
```

### Шаг 2.2: Обновление VoiceProcessingService

**Обновить файл:** `apps/backend/src/Service/AI/VoiceProcessingService.php`

Добавить в конструктор:
```php
use App\Service\AI\Service\CommandEventMapper;
use App\Service\TaskStatsCollector;

// В конструкторе добавить:
private CommandEventMapper $eventMapper;
private TaskStatsCollector $statsCollector;
```

Добавить метод публикации событий:

```php
/**
 * Публикация WebSocket события после выполнения команды
 */
private function publishCommandEvent(
    ParsedCommand $parsedCommand,
    int $userId,
    string $commandId,
    array $executionResult
): void {
    // Проверяем нужно ли публиковать
    if (!$this->eventMapper->shouldPublish($parsedCommand->getAction())) {
        return;
    }

    $eventName = $this->eventMapper->getEventName($parsedCommand->getAction());
    $config = $this->eventMapper->getConfig($parsedCommand->getAction());

    // Подготавливаем данные события
    $eventData = [
        'action' => $parsedCommand->getAction(),
        'command_id' => $commandId,
        'success' => $executionResult['success'] ?? false,
        'message' => $executionResult['message'] ?? null
    ];

    // Добавляем результат выполнения
    if (isset($executionResult['data'])) {
        $eventData['result'] = $executionResult['data'];
    }

    // Добавляем сущность если нужно
    if ($config['includeEntity'] ?? false) {
        if (isset($executionResult['data']['task'])) {
            $eventData['entity'] = $executionResult['data']['task'];
        } elseif (isset($executionResult['data']['tasks'])) {
            $eventData['entities'] = $executionResult['data']['tasks'];
        }
    }

    // Добавляем статистику если нужно
    if ($config['includeStats'] ?? false) {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $eventData['stats'] = $this->statsCollector->collectForUser($user);
        }
    }

    // Для операций с прогрессом
    if ($config['progress'] ?? false) {
        $total = $executionResult['data']['total_count'] ??
                 $executionResult['data']['updated_count'] ??
                 $executionResult['data']['deleted_count'] ?? 0;

        if ($total > 0) {
            // Публикуем начало
            $this->webSocketPublisher->publishProgress(
                $userId,
                $commandId,
                0,
                $total,
                'Начинаю обработку...'
            );

            // Публикуем завершение
            $this->webSocketPublisher->publishProgress(
                $userId,
                $commandId,
                $total,
                $total,
                'Готово!'
            );
        }
    }

    // Публикуем основное событие
    $this->webSocketPublisher->publish($userId, $eventName, $eventData);

    $this->logger->info('Published WebSocket event', [
        'user_id' => $userId,
        'event' => $eventName,
        'action' => $parsedCommand->getAction(),
        'command_id' => $commandId
    ]);
}
```

Обновить метод `processVoiceCommand` после выполнения команды:

```php
// Найти строку где выполняется команда
$executionResult = $this->commandExecutor->execute($parsedCommand, $user);

// Добавить после нее:
if ($executionResult && $executionResult['success']) {
    $this->publishCommandEvent($parsedCommand, $user->getId(), $commandId, $executionResult);
}
```

---

## Фаза 3: JWT аутентификация

### Шаг 3.1: Установка Firebase JWT

```bash
docker exec backend-php83 composer require firebase/php-jwt
```

### Шаг 3.2: Создание CentrifugoController

**Создать файл:** `apps/backend/src/Controller/Api/CentrifugoController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/centrifugo')]
#[IsGranted('ROLE_USER')]
class CentrifugoController extends AbstractController
{
    public function __construct(
        private readonly string $centrifugoSecret
    ) {
    }

    #[Route('/connect', name: 'api_centrifugo_connect', methods: ['POST'])]
    public function connect(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->getId();
        $now = time();

        // Создаем JWT токен для Centrifugo
        $payload = [
            'sub' => (string) $userId,
            'exp' => $now + 3600, // 1 час
            'iat' => $now,
            'info' => [
                'user_id' => $userId,
                'email' => $user->getEmail()
            ],
            'channels' => [
                "personal:{$userId}",
                "personal:{$userId}:voice",
                "personal:{$userId}:tasks"
            ]
        ];

        $token = JWT::encode($payload, $this->centrifugoSecret, 'HS256');

        return $this->json([
            'token' => $token,
            'expires_at' => $payload['exp'],
            'user' => [
                'id' => $userId,
                'email' => $user->getEmail()
            ],
            'ws_url' => $_ENV['CENTRIFUGO_WS_URL'] ?? 'ws://localhost:8000/connection/websocket'
        ]);
    }
}
```

### Шаг 3.3: Добавление конфигурации

**Обновить файл:** `apps/backend/config/services.yaml`

```yaml
parameters:
    env(CENTRIFUGO_TOKEN_HMAC_SECRET_KEY): 'change-me-in-production'
    env(CENTRIFUGO_WS_URL): 'ws://localhost:8000/connection/websocket'

services:
    App\Controller\Api\CentrifugoController:
        arguments:
            $centrifugoSecret: '%env(CENTRIFUGO_TOKEN_HMAC_SECRET_KEY)%'

    App\Service\AI\Service\CommandEventMapper: ~

    App\Service\TaskStatsCollector:
        arguments:
            $taskRepository: '@App\Repository\Database\TaskRepository'
            $tagRepository: '@App\Repository\Database\TagRepository'
```

---

## Фаза 4: Frontend интеграция

### Шаг 4.1: Установка библиотек

```bash
cd apps/frontend
npm install centrifuge @vueuse/motion
```

### Шаг 4.2: Создание ToastStore

**Создать файл:** `apps/frontend/src/stores/toastStore.ts`

```typescript
import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface Toast {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  icon?: string;
  title: string;
  message: string;
  duration?: number;
  progress?: number;
  progressText?: string;
  stats?: Record<string, any>;
  onClick?: () => void;
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([]);
  const toastCounter = ref(0);

  function show(options: Omit<Toast, 'id'>) {
    const id = `toast-${++toastCounter.value}`;
    const toast: Toast = {
      id,
      duration: 5000,
      ...options
    };

    toasts.value.push(toast);

    // Auto remove after duration
    if (toast.duration && toast.duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, toast.duration);
    }

    return id;
  }

  function showProgress(options: Omit<Toast, 'id'>) {
    const existingIndex = toasts.value.findIndex(
      t => t.id === options.id
    );

    if (existingIndex >= 0) {
      // Update existing
      toasts.value[existingIndex] = {
        ...toasts.value[existingIndex],
        ...options
      };
    } else {
      // Create new
      return show(options);
    }
  }

  function updateProgress(commandId: string, updates: Partial<Toast>) {
    const toast = toasts.value.find(t => t.id === commandId);
    if (toast) {
      Object.assign(toast, updates);
    }
  }

  function removeToast(id: string) {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index >= 0) {
      toasts.value.splice(index, 1);
    }
  }

  function clear() {
    toasts.value = [];
  }

  function showSuccess(message: string, title = 'Успешно') {
    return show({
      type: 'success',
      icon: 'pi pi-check-circle',
      title,
      message
    });
  }

  function showError(message: string, title = 'Ошибка') {
    return show({
      type: 'error',
      icon: 'pi pi-times-circle',
      title,
      message,
      duration: 8000
    });
  }

  return {
    toasts,
    show,
    showProgress,
    updateProgress,
    removeToast,
    clear,
    showSuccess,
    showError
  };
});
```

### Шаг 4.3: Создание GlassmorphismToast компонента

**Создать файл:** `apps/frontend/src/components/notifications/GlassmorphismToast.vue`

```vue
<template>
  <Teleport to="body">
    <TransitionGroup name="toast" tag="div" class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast-notification"
        :class="toast.type"
        @click="handleToastClick(toast)"
      >
        <!-- Glassmorphism backdrop -->
        <div class="toast-backdrop"></div>

        <!-- Content -->
        <div class="toast-content">
          <!-- Animated icon -->
          <div class="toast-icon-wrapper">
            <div class="toast-icon-pulse"></div>
            <i :class="toast.icon || getDefaultIcon(toast.type)" class="toast-icon"></i>
          </div>

          <!-- Body -->
          <div class="toast-body">
            <h4 class="toast-title">{{ toast.title }}</h4>
            <p class="toast-message">{{ toast.message }}</p>

            <!-- Progress bar for bulk operations -->
            <div v-if="toast.progress !== undefined" class="toast-progress">
              <div class="progress-bar">
                <div
                  class="progress-fill"
                  :style="{width: toast.progress + '%'}"
                >
                  <div class="progress-glow"></div>
                </div>
              </div>
              <span class="progress-text">{{ toast.progressText }}</span>
            </div>

            <!-- Statistics -->
            <div v-if="toast.stats" class="toast-stats">
              <div
                v-for="(value, label) in formatStats(toast.stats)"
                :key="label"
                class="stat-item"
              >
                <span class="stat-label">{{ label }}:</span>
                <span class="stat-value">{{ value }}</span>
              </div>
            </div>
          </div>

          <!-- Close button -->
          <button
            @click.stop="dismiss(toast.id)"
            class="toast-close"
            aria-label="Закрыть"
          >
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useToastStore } from '@/stores/toastStore';

const toastStore = useToastStore();
const toasts = computed(() => toastStore.toasts);

const statLabels = {
  tasks_total: 'Всего',
  tasks_pending: 'Ожидают',
  tasks_in_progress: 'В работе',
  tasks_completed: 'Выполнено',
  completed_today: 'Сегодня',
  due_soon: 'Скоро срок',
  overdue: 'Просрочено'
};

function getDefaultIcon(type: string): string {
  const icons = {
    success: 'pi pi-check-circle',
    error: 'pi pi-times-circle',
    warning: 'pi pi-exclamation-triangle',
    info: 'pi pi-info-circle'
  };
  return icons[type] || 'pi pi-info-circle';
}

function formatStats(stats: any) {
  if (!stats) return {};

  const result: Record<string, any> = {};

  // Показываем только ключевые метрики
  const keysToShow = ['tasks_total', 'tasks_completed', 'completed_today'];

  for (const key of keysToShow) {
    if (stats[key] !== undefined && statLabels[key]) {
      result[statLabels[key]] = stats[key];
    }
  }

  return result;
}

function dismiss(id: string) {
  toastStore.removeToast(id);
}

function handleToastClick(toast: any) {
  if (toast.onClick) {
    toast.onClick();
  }
}
</script>

<style scoped>
/* Контейнер для уведомлений */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  pointer-events: none;
  max-width: 420px;
}

.toast-notification {
  position: relative;
  min-width: 350px;
  margin-bottom: 16px;
  pointer-events: all;
  cursor: default;
}

/* Glassmorphism backdrop */
.toast-backdrop {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(59, 130, 246, 0.12) 0%,
    rgba(147, 197, 253, 0.08) 50%,
    rgba(59, 130, 246, 0.06) 100%
  );
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  box-shadow:
    0 8px 32px rgba(59, 130, 246, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.2),
    inset 0 -1px 0 rgba(0, 0, 0, 0.05);
}

.toast-content {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  color: white;
}

/* Иконка с пульсацией */
.toast-icon-wrapper {
  position: relative;
  flex-shrink: 0;
}

.toast-icon {
  width: 24px;
  height: 24px;
  color: #60a5fa;
  filter: drop-shadow(0 0 12px rgba(96, 165, 250, 0.6));
  z-index: 2;
  position: relative;
}

.toast-icon-pulse {
  position: absolute;
  inset: -12px;
  background: radial-gradient(
    circle,
    rgba(96, 165, 250, 0.4) 0%,
    rgba(96, 165, 250, 0.2) 40%,
    transparent 70%
  );
  border-radius: 50%;
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.5);
    opacity: 0;
  }
}

/* Текстовый контент */
.toast-body {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: rgba(255, 255, 255, 0.95);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.toast-message {
  font-size: 13px;
  margin: 0;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.4;
  word-break: break-word;
}

/* Прогресс бар */
.toast-progress {
  margin-top: 12px;
}

.progress-bar {
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  overflow: hidden;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 50%, #93bbfc 100%);
  border-radius: 2px;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.progress-glow {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 50px;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.4),
    transparent
  );
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-50px); }
  100% { transform: translateX(50px); }
}

.progress-text {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 4px;
  display: inline-block;
}

/* Статистика */
.toast-stats {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.stat-item {
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.stat-label {
  color: rgba(255, 255, 255, 0.6);
}

.stat-value {
  color: #60a5fa;
  font-weight: 600;
}

/* Кнопка закрытия */
.toast-close {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.6);
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.toast-close:hover {
  background: rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.9);
  transform: scale(1.1);
}

/* Анимации появления/исчезновения */
.toast-enter-active {
  animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-leave-active {
  animation: slideOutRight 0.3s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes slideInRight {
  from {
    transform: translateX(120%) scale(0.9);
    opacity: 0;
    filter: blur(4px);
  }
  to {
    transform: translateX(0) scale(1);
    opacity: 1;
    filter: blur(0);
  }
}

@keyframes slideOutRight {
  to {
    transform: translateX(120%) scale(0.9);
    opacity: 0;
    filter: blur(4px);
  }
}

/* Варианты цветов для разных типов */
.toast-notification.success .toast-backdrop {
  background: linear-gradient(
    135deg,
    rgba(16, 185, 129, 0.12) 0%,
    rgba(16, 185, 129, 0.08) 100%
  );
}

.toast-notification.success .toast-icon {
  color: #10b981;
  filter: drop-shadow(0 0 12px rgba(16, 185, 129, 0.6));
}

.toast-notification.success .toast-icon-pulse {
  background: radial-gradient(
    circle,
    rgba(16, 185, 129, 0.4) 0%,
    rgba(16, 185, 129, 0.2) 40%,
    transparent 70%
  );
}

.toast-notification.error .toast-backdrop {
  background: linear-gradient(
    135deg,
    rgba(239, 68, 68, 0.12) 0%,
    rgba(239, 68, 68, 0.08) 100%
  );
}

.toast-notification.error .toast-icon {
  color: #ef4444;
  filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.6));
}

.toast-notification.warning .toast-backdrop {
  background: linear-gradient(
    135deg,
    rgba(245, 158, 11, 0.12) 0%,
    rgba(245, 158, 11, 0.08) 100%
  );
}

.toast-notification.warning .toast-icon {
  color: #f59e0b;
  filter: drop-shadow(0 0 12px rgba(245, 158, 11, 0.6));
}

/* Hover эффект */
.toast-notification:hover .toast-backdrop {
  backdrop-filter: blur(24px) saturate(200%);
  border-color: rgba(255, 255, 255, 0.25);
}
</style>
```

### Шаг 4.4: Создание useCentrifugo composable

**Создать файл:** `apps/frontend/src/composables/useCentrifugo.ts`

```typescript
import { ref, onMounted, onUnmounted } from 'vue';
import { Centrifuge, Subscription } from 'centrifuge';
import { useAuthStore } from '@/stores/authStore';
import { useToastStore } from '@/stores/toastStore';
import { useTaskStore } from '@/stores/taskStore';
import { apiService } from '@/services/apiService';

export function useCentrifugo() {
  const authStore = useAuthStore();
  const toastStore = useToastStore();
  const taskStore = useTaskStore();

  const centrifuge = ref<Centrifuge | null>(null);
  const subscriptions = ref<Map<string, Subscription>>(new Map());
  const connectionState = ref<'disconnected' | 'connecting' | 'connected'>('disconnected');

  // Обработчики событий
  const eventHandlers: Record<string, (data: any) => void> = {
    // Создание задачи
    'task.created': (data: any) => {
      if (data.entity) {
        taskStore.addTask(data.entity);
      }

      toastStore.show({
        type: 'success',
        icon: 'pi pi-check-circle',
        title: '✨ Задача создана',
        message: data.entity?.title || 'Новая задача добавлена',
        stats: data.stats,
        duration: 5000
      });
    },

    // Создание нескольких задач
    'tasks.created': (data: any) => {
      const tasks = data.entities || [];
      tasks.forEach((task: any) => taskStore.addTask(task));

      toastStore.show({
        type: 'success',
        icon: 'pi pi-plus-circle',
        title: `✨ Создано задач: ${tasks.length}`,
        message: 'Все задачи успешно добавлены',
        stats: data.stats,
        duration: 5000
      });
    },

    // Завершение задачи
    'task.completed': (data: any) => {
      if (data.entity?.id) {
        taskStore.updateTask(data.entity.id, { status: 'COMPLETED' });
      }

      toastStore.show({
        type: 'success',
        icon: 'pi pi-check',
        title: '✅ Задача выполнена!',
        message: data.entity?.title || 'Задача завершена',
        stats: data.stats,
        duration: 4000
      });
    },

    // Отмена завершения
    'task.uncompleted': (data: any) => {
      if (data.entity?.id) {
        taskStore.updateTask(data.entity.id, { status: 'PENDING' });
      }

      toastStore.show({
        type: 'info',
        icon: 'pi pi-undo',
        title: '↩️ Задача возвращена',
        message: data.entity?.title || 'Задача снова активна',
        stats: data.stats,
        duration: 4000
      });
    },

    // Обновление задачи
    'task.updated': (data: any) => {
      if (data.entity?.id) {
        taskStore.updateTask(data.entity.id, data.entity);
      }

      toastStore.show({
        type: 'info',
        icon: 'pi pi-refresh',
        title: '📝 Задача обновлена',
        message: data.message || 'Изменения сохранены',
        duration: 3000
      });
    },

    // Удаление задачи
    'task.deleted': (data: any) => {
      const taskId = data.result?.task_id || data.entity?.id;
      if (taskId) {
        taskStore.removeTask(taskId);
      }

      toastStore.show({
        type: 'info',
        icon: 'pi pi-trash',
        title: 'Задача удалена',
        message: data.message || 'Задача была удалена',
        stats: data.stats,
        duration: 3000
      });
    },

    // Добавление тега
    'task.tag_added': (data: any) => {
      if (data.entity?.id) {
        taskStore.updateTask(data.entity.id, data.entity);
      }

      toastStore.show({
        type: 'success',
        icon: 'pi pi-tag',
        title: '🏷️ Тег добавлен',
        message: data.message || 'Тег успешно добавлен к задаче',
        duration: 3000
      });
    },

    // Удаление тега
    'task.tag_removed': (data: any) => {
      if (data.entity?.id) {
        taskStore.updateTask(data.entity.id, data.entity);
      }

      toastStore.show({
        type: 'info',
        icon: 'pi pi-tag',
        title: '🏷️ Тег удален',
        message: data.message || 'Тег удален из задачи',
        duration: 3000
      });
    },

    // Массовые операции
    'tasks.bulk_completed': (data: any) => {
      toastStore.show({
        type: 'success',
        icon: 'pi pi-check-circle',
        title: '🎉 Массовое завершение',
        message: data.message || `Завершено задач: ${data.result?.success_count || 0}`,
        stats: data.stats,
        duration: 5000
      });

      // Обновляем список задач
      taskStore.fetchTasks();
    },

    'tasks.bulk_updated': (data: any) => {
      toastStore.show({
        type: 'success',
        icon: 'pi pi-refresh',
        title: '📝 Массовое обновление',
        message: data.message || `Обновлено задач: ${data.result?.updated_count || 0}`,
        stats: data.stats,
        duration: 5000
      });

      // Обновляем список задач
      taskStore.fetchTasks();
    },

    'tasks.bulk_deleted': (data: any) => {
      toastStore.show({
        type: 'info',
        icon: 'pi pi-trash',
        title: '🗑️ Массовое удаление',
        message: data.message || `Удалено задач: ${data.result?.deleted_count || 0}`,
        stats: data.stats,
        duration: 5000
      });

      // Обновляем список задач
      taskStore.fetchTasks();
    },

    // Очистка завершенных
    'tasks.cleanup_completed': (data: any) => {
      toastStore.show({
        type: 'success',
        icon: 'pi pi-sparkles',
        title: '✨ Очистка завершена',
        message: data.message || `Удалено завершенных задач: ${data.result?.deleted_count || 0}`,
        stats: data.stats,
        duration: 5000
      });

      // Обновляем список задач
      taskStore.fetchTasks();
    },

    // Прогресс операции
    'progress.update': (data: any) => {
      const progress = data.progress;
      if (progress) {
        toastStore.updateProgress(data.command_id, {
          progress: progress.percent,
          progressText: `${progress.current} из ${progress.total}`,
          message: progress.message
        });
      }
    }
  };

  async function connect() {
    if (!authStore.isAuthenticated) {
      console.log('⚠️ User not authenticated, skipping WebSocket connection');
      return;
    }

    try {
      connectionState.value = 'connecting';

      // Получаем JWT токен для Centrifugo
      const response = await apiService.post('/api/centrifugo/connect');
      const { token, ws_url } = response.data;

      // Создаем клиент Centrifuge
      centrifuge.value = new Centrifuge(ws_url || 'ws://localhost:8000/connection/websocket', {
        token,
        debug: import.meta.env.DEV
      });

      // Обработчики событий соединения
      centrifuge.value.on('connecting', (ctx) => {
        connectionState.value = 'connecting';
        console.log('🔄 Connecting to Centrifugo...', ctx);
      });

      centrifuge.value.on('connected', (ctx) => {
        connectionState.value = 'connected';
        console.log('✅ Connected to Centrifugo', ctx);

        // Показываем уведомление о подключении
        toastStore.show({
          type: 'success',
          icon: 'pi pi-wifi',
          title: 'Подключено',
          message: 'Real-time обновления активны',
          duration: 2000
        });
      });

      centrifuge.value.on('disconnected', (ctx) => {
        connectionState.value = 'disconnected';
        console.log('❌ Disconnected from Centrifugo', ctx);

        // Показываем уведомление об отключении
        toastStore.show({
          type: 'warning',
          icon: 'pi pi-wifi',
          title: 'Отключено',
          message: 'Real-time обновления недоступны',
          duration: 3000
        });
      });

      // Подключаемся
      centrifuge.value.connect();

      // Подписываемся на каналы пользователя
      subscribeToChannels(response.data.user.id);

    } catch (error) {
      connectionState.value = 'disconnected';
      console.error('Failed to connect to Centrifugo:', error);

      toastStore.showError(
        'Не удалось подключиться к real-time обновлениям',
        'Ошибка подключения'
      );
    }
  }

  function subscribeToChannels(userId: number) {
    if (!centrifuge.value) return;

    // Личный канал пользователя
    const personalChannel = centrifuge.value.newSubscription(`personal:${userId}`);

    personalChannel.on('publication', (ctx) => {
      console.log('📨 Received message in personal channel:', ctx.data);
      handleMessage(ctx.data);
    });

    personalChannel.on('error', (ctx) => {
      console.error('Error in personal channel:', ctx);
    });

    personalChannel.subscribe();
    subscriptions.value.set(`personal:${userId}`, personalChannel);
  }

  function handleMessage(data: any) {
    const event = data.event;

    if (event && eventHandlers[event]) {
      console.log(`🎯 Handling event: ${event}`, data.data);
      eventHandlers[event](data.data);
    } else {
      console.warn(`⚠️ No handler for event: ${event}`, data);
    }
  }

  function disconnect() {
    // Отписываемся от всех каналов
    subscriptions.value.forEach(sub => {
      sub.unsubscribe();
      sub.removeAllListeners();
    });
    subscriptions.value.clear();

    // Отключаемся от Centrifugo
    if (centrifuge.value) {
      centrifuge.value.disconnect();
      centrifuge.value.removeAllListeners();
      centrifuge.value = null;
    }

    connectionState.value = 'disconnected';
  }

  // Автоматическое подключение при монтировании
  onMounted(() => {
    connect();
  });

  // Отключение при размонтировании
  onUnmounted(() => {
    disconnect();
  });

  return {
    connectionState,
    connect,
    disconnect,
    isConnected: computed(() => connectionState.value === 'connected')
  };
}
```

### Шаг 4.5: Подключение Toast в App.vue

**Обновить файл:** `apps/frontend/src/App.vue`

Добавить в template:
```vue
<template>
  <div id="app">
    <!-- Existing content -->
    <router-view />

    <!-- Toast notifications -->
    <GlassmorphismToast />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import GlassmorphismToast from '@/components/notifications/GlassmorphismToast.vue';
import { useCentrifugo } from '@/composables/useCentrifugo';

// Инициализация WebSocket соединения
const { connect } = useCentrifugo();

onMounted(() => {
  // WebSocket подключается автоматически в composable
  console.log('App mounted, WebSocket connection initiated');
});
</script>
```

---

## Фаза 5: Статистика

### Шаг 5.1: Обновление TaskRepository

**Обновить файл:** `apps/backend/src/Repository/Database/TaskRepository.php`

Добавить методы для статистики:

```php
/**
 * Подсчет всех задач пользователя
 */
public function countByUser(User $user): int
{
    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Подсчет задач по статусу
 */
public function countByStatus(User $user, TaskStatus $status): int
{
    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->andWhere('t.status = :status')
        ->setParameter('user', $user)
        ->setParameter('status', $status)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Подсчет задач завершенных сегодня
 */
public function countCompletedToday(User $user): int
{
    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    $tomorrow = clone $today;
    $tomorrow->modify('+1 day');

    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->andWhere('t.status = :status')
        ->andWhere('t.completedAt >= :today')
        ->andWhere('t.completedAt < :tomorrow')
        ->setParameter('user', $user)
        ->setParameter('status', TaskStatus::COMPLETED)
        ->setParameter('today', $today)
        ->setParameter('tomorrow', $tomorrow)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Подсчет задач со сроком в ближайшие дни
 */
public function countDueSoon(User $user, int $days = 3): int
{
    $now = new \DateTime();
    $future = new \DateTime("+{$days} days");

    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->andWhere('t.status != :completed')
        ->andWhere('t.dueDate IS NOT NULL')
        ->andWhere('t.dueDate > :now')
        ->andWhere('t.dueDate <= :future')
        ->setParameter('user', $user)
        ->setParameter('completed', TaskStatus::COMPLETED)
        ->setParameter('now', $now)
        ->setParameter('future', $future)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Подсчет просроченных задач
 */
public function countOverdue(User $user): int
{
    $now = new \DateTime();

    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->andWhere('t.status != :completed')
        ->andWhere('t.dueDate IS NOT NULL')
        ->andWhere('t.dueDate < :now')
        ->setParameter('user', $user)
        ->setParameter('completed', TaskStatus::COMPLETED)
        ->setParameter('now', $now)
        ->getQuery()
        ->getSingleScalarResult();
}
```

### Шаг 5.2: Обновление TagRepository

**Обновить файл:** `apps/backend/src/Repository/Database/TagRepository.php`

Добавить метод:

```php
/**
 * Подсчет тегов пользователя
 */
public function countByUser(User $user): int
{
    return $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getSingleScalarResult();
}
```

---

## Фаза 6: Production настройка

### Шаг 6.1: Обновление Nginx конфигурации

**Обновить файл:** `infrastructure/nginx/sites/backend.conf`

Добавить location для WebSocket:

```nginx
# WebSocket connection for Centrifugo
location /connection/websocket {
    proxy_pass http://centrifugo:8000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    # WebSocket specific timeouts
    proxy_connect_timeout 7d;
    proxy_send_timeout 7d;
    proxy_read_timeout 7d;

    # Disable buffering for WebSocket
    proxy_buffering off;
}

# Centrifugo API
location /api/publish {
    proxy_pass http://centrifugo:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### Шаг 6.2: Обновление Environment переменных

**Обновить файл:** `.env.docker`

```bash
# Centrifugo
CENTRIFUGO_PORT=8000
CENTRIFUGO_ADMIN_PORT=8001
CENTRIFUGO_API_KEY=your-api-key-here
CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=your-secret-key-here
CENTRIFUGO_ADMIN_PASSWORD=admin-password
CENTRIFUGO_ADMIN_SECRET=admin-secret
```

### Шаг 6.3: Обновление centrifugo.json

**Обновить файл:** `infrastructure/centrifugo/centrifugo.json`

```json
{
  "token_hmac_secret_key": "${CENTRIFUGO_TOKEN_HMAC_SECRET_KEY}",
  "api_key": "${CENTRIFUGO_API_KEY}",
  "admin": true,
  "admin_password": "${CENTRIFUGO_ADMIN_PASSWORD}",
  "admin_secret": "${CENTRIFUGO_ADMIN_SECRET}",
  "allowed_origins": [
    "http://localhost:3000",
    "http://localhost:8089",
    "https://yourdomain.com"
  ],
  "namespaces": [
    {
      "name": "personal",
      "presence": false,
      "join_leave": false,
      "history_size": 50,
      "history_ttl": "60s",
      "history_recover": true,
      "allow_subscribe_for_client": false,
      "allow_publish_for_client": false,
      "allow_presence_for_client": false,
      "allow_history_for_client": false
    }
  ],
  "client_insecure": false,
  "client_anonymous": false,
  "client_connection_limit": 0,
  "channel_max_length": 255,
  "user_connection_limit": 10,
  "user_subscribe_to_personal": true,
  "stale_connection_close_delay": "25s",
  "expired_connection_close_delay": "25s",
  "client_stale_close_delay": "25s",
  "client_expired_close_delay": "25s",
  "client_expired_sub_close_delay": "25s",
  "shutdown_timeout": "30s"
}
```

---

## Тестирование

### Шаг 1: Проверка Centrifugo

```bash
# Проверить что Centrifugo запущен
docker-compose ps | grep centrifugo

# Проверить health endpoint
curl http://localhost:8000/health

# Проверить admin панель
# Открыть в браузере: http://localhost:8001
# Логин: admin
# Пароль: из CENTRIFUGO_ADMIN_PASSWORD
```

### Шаг 2: Проверка Backend

```bash
# Проверить установку phpcent
docker exec backend-php83 composer show | grep phpcent

# Проверить endpoint JWT
curl -X POST http://localhost:8089/api/centrifugo/connect \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Шаг 3: Проверка Frontend

1. Открыть приложение в браузере
2. Открыть DevTools → Console
3. Должны увидеть сообщения:
   - `🔄 Connecting to Centrifugo...`
   - `✅ Connected to Centrifugo`
4. Выполнить голосовую команду
5. Должен появиться glassmorphism toast

### Шаг 4: Тестовая команда

```bash
# Создать задачу через API
curl -X POST http://localhost:8089/api/voice/command \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text": "Создай задачу тестовая задача для проверки websocket"}'
```

Ожидаемый результат:
- Toast уведомление появляется справа вверху
- Список задач обновляется автоматически
- В консоли видны логи получения события

---

## Чеклист выполнения

### Backend ✅ Выполнено 2025-11-21
- [x] Установить phpcent библиотеку
- [x] Создать CommandEventMapper
- [ ] Создать TaskStatsCollector (опционально)
- [x] Обновить WebSocketPublisher
- [x] Интегрировать в VoiceCommandExecutor
- [x] Создать WebSocketController (JWT токены)
- [x] Создать CentrifugoTokenProvider
- [x] Обновить конфигурацию services.yaml

### Frontend ✅ Выполнено 2025-11-21
- [x] Установить centrifuge-js
- [ ] Создать ToastStore (опционально)
- [ ] Создать GlassmorphismToast компонент (опционально)
- [x] Создать useWebSocket composable
- [x] Создать websocket.service.ts
- [x] Интегрировать в TasksDashboardView
- [x] Обработка событий задач в реальном времени

### Infrastructure ✅ Выполнено
- [x] Настроить environment переменные
- [x] Centrifugo работает в Docker (docker-compose.ai.yml)
- [x] Redis для Centrifugo

### Оставшиеся задачи (опционально)
- [ ] GlassmorphismToast компонент
- [ ] TaskStatsCollector для статистики
- [ ] Nginx proxy для production
- [ ] Unit тесты для WebSocket сервисов

---

## Возможные проблемы и решения

### Проблема: Centrifugo не запускается

```bash
# Проверить логи
docker logs centrifugo

# Проверить конфигурацию
docker exec centrifugo cat /centrifugo/config.json
```

### Проблема: WebSocket не подключается

1. Проверить CORS настройки в centrifugo.json
2. Проверить Nginx proxy настройки
3. Проверить JWT токен

### Проблема: События не приходят

1. Проверить что WebSocket enabled в конфигурации
2. Проверить логи Backend
3. Проверить подписку на правильный канал

---

## Результат

После выполнения всех шагов вы получите:

1. ✅ Real-time обновления для всех операций с задачами
2. ✅ Красивые glassmorphism toast уведомления
3. ✅ Автоматическое обновление UI без перезагрузки
4. ✅ Статистика в реальном времени
5. ✅ Прогресс для массовых операций
6. ✅ Надежное WebSocket соединение с автореконнектом

---

**Автор**: Claude AI Assistant
**Дата создания**: 2024-11-21
**Версия**: 1.0.0