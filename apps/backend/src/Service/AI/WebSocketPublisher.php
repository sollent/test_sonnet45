<?php

declare(strict_types=1);

namespace App\Service\AI;

use Exception;
use phpcent\Client;
use Psr\Log\LoggerInterface;

/**
 * Сервис публикации сообщений через WebSocket (Centrifugo)
 *
 * Отправляет real-time уведомления пользователям о статусе обработки команд.
 * Использует паттерн Publisher из Pub/Sub архитектуры.
 * Обновлен для использования phpcent библиотеки вместо HTTP клиента.
 */
class WebSocketPublisher
{
    private Client $centrifugo;

    private LoggerInterface $logger;

    private bool $enabled;

    public function __construct(
        LoggerInterface $logger,
        string $centrifugoUrl,
        string $centrifugoApiKey,
        bool $enabled = true
    ) {
        $this->logger = $logger;
        $this->enabled = $enabled;

        // Инициализируем phpcent клиент
        $this->centrifugo = new Client($centrifugoUrl);
        $this->centrifugo->setApiKey($centrifugoApiKey);
    }

    /**
     * Публикация сообщения пользователю
     *
     * @param int    $userId ID пользователя
     * @param string $event  Тип события
     * @param array  $data   Данные для отправки
     *
     * @return bool Успешность отправки
     */
    public function publish(int $userId, string $event, array $data): bool
    {
        if (!$this->enabled) {
            $this->logger->debug('WebSocket publishing is disabled');

            return true;
        }

        $channel = $this->getUserChannel($userId);

        $this->logger->info('Publishing to WebSocket', [
            'channel' => $channel,
            'event'   => $event,
            'user_id' => $userId,
        ]);

        try {
            $message = [
                'event'     => $event,
                'data'      => $data,
                'timestamp' => time(),
            ];

            $this->centrifugo->publish($channel, $message);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to publish to WebSocket', [
                'channel' => $channel,
                'event'   => $event,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Публикация глобального сообщения (broadcast)
     *
     * @param string $event Тип события
     * @param array  $data  Данные для отправки
     *
     * @return bool Успешность отправки
     */
    public function broadcast(string $event, array $data): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $channel = 'global';

        $this->logger->info('Broadcasting to all users', [
            'event'   => $event,
            'channel' => $channel,
        ]);

        try {
            $message = [
                'event'     => $event,
                'data'      => $data,
                'timestamp' => time(),
            ];

            $this->centrifugo->publish($channel, $message);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Публикация статуса обработки голосовой команды
     */
    public function publishVoiceCommandStatus(
        int $userId,
        int $commandId,
        string $status,
        array $additionalData = [],
    ): bool {
        return $this->publish($userId, 'voice_command_status', array_merge([
            'command_id' => $commandId,
            'status'     => $status,
        ], $additionalData));
    }

    /**
     * Публикация результата выполнения команды
     */
    public function publishCommandResult(
        int $userId,
        int $commandId,
        bool $success,
        array $result,
    ): bool {
        $event = $success ? 'voice_command_success' : 'voice_command_failed';

        return $this->publish($userId, $event, [
            'command_id' => $commandId,
            'result'     => $result,
        ]);
    }

    /**
     * Проверка доступности Centrifugo
     */
    public function isAvailable(): bool
    {
        try {
            $this->centrifugo->info();

            return true;
        } catch (Exception $e) {
            $this->logger->warning('Centrifugo health check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Отправка типизированных событий для голосовых команд
     */
    public function sendVoiceEvent(int $userId, VoiceEventType $type, array $data): bool
    {
        $eventMap = [
            VoiceEventType::PROCESSING_STARTED->value => [
                'title' => 'Обработка началась',
                'icon'  => '🎤',
            ],
            VoiceEventType::TRANSCRIPTION_COMPLETED->value => [
                'title' => 'Транскрипция завершена',
                'icon'  => '📝',
            ],
            VoiceEventType::COMMAND_PARSED->value => [
                'title' => 'Команда распознана',
                'icon'  => '🧠',
            ],
            VoiceEventType::COMMAND_EXECUTING->value => [
                'title' => 'Выполнение команды',
                'icon'  => '⚡',
            ],
            VoiceEventType::COMMAND_COMPLETED->value => [
                'title' => 'Команда выполнена',
                'icon'  => '✅',
            ],
            VoiceEventType::COMMAND_FAILED->value => [
                'title' => 'Ошибка выполнения',
                'icon'  => '❌',
            ],
            VoiceEventType::CLARIFICATION_NEEDED->value => [
                'title' => 'Требуется уточнение',
                'icon'  => '❓',
            ],
        ];

        $eventInfo = $eventMap[$type->value];

        return $this->publish($userId, 'voice_event', array_merge($eventInfo, [
            'type' => $type->value,
            'data' => $data,
        ]));
    }

    /**
     * Получение канала для пользователя
     */
    private function getUserChannel(int $userId): string
    {
        return sprintf('personal:%d', $userId);
    }

    /**
     * Отключить пользователя от всех каналов
     */
    public function disconnectUser(int $userId): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $this->centrifugo->disconnect((string) $userId);

            $this->logger->info('User disconnected from Centrifugo', [
                'user_id' => $userId,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to disconnect user', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить информацию о подключениях к каналу
     */
    public function getPresence(string $channel): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            return $this->centrifugo->presence($channel);
        } catch (Exception $e) {
            $this->logger->error('Failed to get presence', [
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }
    }
}

/**
 * Типы событий для голосовых команд
 */
enum VoiceEventType: string
{
    case PROCESSING_STARTED = 'processing_started';
    case TRANSCRIPTION_COMPLETED = 'transcription_completed';
    case COMMAND_PARSED = 'command_parsed';
    case COMMAND_EXECUTING = 'command_executing';
    case COMMAND_COMPLETED = 'command_completed';
    case COMMAND_FAILED = 'command_failed';
    case CLARIFICATION_NEEDED = 'clarification_needed';
}
