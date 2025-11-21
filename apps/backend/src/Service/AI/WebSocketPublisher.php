<?php

declare(strict_types=1);

namespace App\Service\AI;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Сервис публикации сообщений через WebSocket (Centrifugo)
 *
 * Отправляет real-time уведомления пользователям о статусе обработки команд.
 * Использует паттерн Publisher из Pub/Sub архитектуры
 */
class WebSocketPublisher
{
    private HttpClientInterface $httpClient;

    private LoggerInterface $logger;

    private string $centrifugoUrl;

    private string $centrifugoApiKey;

    private bool $enabled;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        ParameterBagInterface $params,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;

        // Конфигурация Centrifugo
        /** @var string $centrifugoUrl */
        $centrifugoUrl = $params->has('centrifugo_url') ? $params->get('centrifugo_url') : 'http://centrifugo:8000';
        $this->centrifugoUrl = $centrifugoUrl;

        /** @var string $apiKey */
        $apiKey = $params->has('centrifugo_api_key') ? $params->get('centrifugo_api_key') : 'default-api-key';
        $this->centrifugoApiKey = $apiKey;

        $this->enabled = $params->has('websocket_enabled') && (bool) $params->get('websocket_enabled');
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

            return $this->sendToCentrifugo($channel, $message);

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

            return $this->sendToCentrifugo($channel, $message);

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
            $response = $this->httpClient->request('GET', $this->centrifugoUrl . '/health', [
                'timeout' => 2.0,
            ]);

            return $response->getStatusCode() === 200;
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

        $eventInfo = $eventMap[$type->value] ?? [
            'title' => 'Событие',
            'icon'  => '📢',
        ];

        return $this->publish($userId, 'voice_event', array_merge($eventInfo, [
            'type' => $type->value,
            'data' => $data,
        ]));
    }

    /**
     * Отправка данных в Centrifugo
     */
    private function sendToCentrifugo(string $channel, array $message): bool
    {
        try {
            $response = $this->httpClient->request('POST', $this->centrifugoUrl . '/api/publish', [
                'headers' => [
                    'Authorization' => 'apikey ' . $this->centrifugoApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'channel' => $channel,
                    'data'    => $message,
                ],
                'timeout' => 5.0,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new RuntimeException(sprintf(
                    'Centrifugo returned status %d',
                    $statusCode,
                ));
            }

            $responseData = $response->toArray();

            // Проверяем ответ Centrifugo
            if (isset($responseData['error'])) {
                throw new RuntimeException(
                    'Centrifugo error: ' . ($responseData['error']['message'] ?? 'Unknown error'),
                );
            }

            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send to Centrifugo', [
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получение канала для пользователя
     */
    private function getUserChannel(int $userId): string
    {
        return sprintf('user:%d', $userId);
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
