<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\User;
use App\Entity\VoiceCommand;
use App\Repository\Database\VoiceCommandRepository;
use App\Service\AI\LLMService;
use App\Service\AI\SmartSearchService;
use App\Service\AI\VoiceCommandExecutor;
use App\Service\AI\WebSocketPublisher;
use App\ValueObject\CommandStatus;
use App\ValueObject\CommandType;
use App\ValueObject\ParsedCommand;
use App\ValueObject\TranscriptionResult;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use RuntimeException;

/**
 * Основной сервис обработки голосовых команд
 *
 * Координирует весь процесс: транскрипция → парсинг → выполнение → уведомление.
 * Использует паттерн Facade для упрощения сложного взаимодействия между сервисами.
 */
class VoiceProcessingService
{
    private VoiceCommandRepository $commandRepository;
    private LLMService $llmService;
    private VoiceCommandExecutor $commandExecutor;
    private WebSocketPublisher $wsPublisher;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $whisperUrl;

    public function __construct(
        VoiceCommandRepository $commandRepository,
        LLMService $llmService,
        VoiceCommandExecutor $commandExecutor,
        WebSocketPublisher $wsPublisher,
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $whisperUrl = 'http://whisper:9000'
    ) {
        $this->commandRepository = $commandRepository;
        $this->llmService = $llmService;
        $this->commandExecutor = $commandExecutor;
        $this->wsPublisher = $wsPublisher;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->whisperUrl = $whisperUrl;
    }

    /**
     * Обработка голосовой команды (аудио)
     *
     * @param string $audioUrl URL аудиофайла
     * @param User $user Пользователь
     * @return VoiceCommand Созданная и обработанная команда
     */
    public function processVoiceAudio(string $audioUrl, User $user): VoiceCommand
    {
        $this->logger->info('Processing voice audio command', [
            'audio_url' => $audioUrl,
            'user_id' => $user->getId()
        ]);

        // Создаем запись команды
        $command = new VoiceCommand(
            user: $user,
            commandType: CommandType::VOICE_AUDIO,
            rawAudioUrl: $audioUrl
        );

        $this->commandRepository->save($command);

        // Уведомляем через WebSocket о начале обработки
        $this->notifyStatus($command, 'processing_started');

        try {
            // Запускаем обработку
            $command->startProcessing();
            $this->commandRepository->save($command);

            // Шаг 1: Транскрипция аудио
            $transcription = $this->transcribeAudio($audioUrl);
            $command->setTranscriptionResult($transcription);
            $this->commandRepository->save($command);

            // Уведомляем о завершении транскрипции
            $this->notifyStatus($command, 'transcription_completed', [
                'text' => $transcription->text,
                'language' => $transcription->language
            ]);

            // Продолжаем обработку текста
            $this->processCommandText($command, $transcription->text, $user);

        } catch (\Exception $e) {
            $this->handleError($command, $e);
        }

        return $command;
    }

    /**
     * Обработка текстовой команды
     *
     * @param string $text Текст команды
     * @param User $user Пользователь
     * @return VoiceCommand Созданная и обработанная команда
     */
    public function processVoiceText(string $text, User $user): VoiceCommand
    {
        $this->logger->info('Processing voice text command', [
            'text' => $text,
            'user_id' => $user->getId()
        ]);

        // Создаем запись команды
        $command = new VoiceCommand(
            user: $user,
            commandType: CommandType::VOICE_TEXT,
            transcribedText: $text
        );

        $this->commandRepository->save($command);

        // Уведомляем через WebSocket
        $this->notifyStatus($command, 'processing_started');

        try {
            // Запускаем обработку
            $command->startProcessing();
            $this->commandRepository->save($command);

            // Обрабатываем текст
            $this->processCommandText($command, $text, $user);

        } catch (\Exception $e) {
            $this->handleError($command, $e);
        }

        return $command;
    }

    /**
     * Обработка текста команды (общая логика)
     */
    private function processCommandText(VoiceCommand $command, string $text, User $user): void
    {
        // Шаг 2: Парсинг команды через LLM
        $parsedCommand = $this->llmService->parseCommand($text);
        $command->setParsedCommand($parsedCommand);
        $this->commandRepository->save($command);

        // Уведомляем о парсинге
        $this->notifyStatus($command, 'command_parsed', [
            'action' => $parsedCommand->action,
            'confidence' => $parsedCommand->confidence,
            'parameters' => $parsedCommand->parameters
        ]);

        // Если команда не выполнима (низкая уверенность или требует уточнения)
        if (!$parsedCommand->isExecutable()) {
            $clarificationMessage = $parsedCommand->getClarificationQuestion();
            $command->markAsFailed($clarificationMessage ?? 'Команда требует уточнения');
            $this->commandRepository->save($command);

            $this->notifyStatus($command, 'clarification_needed', [
                'question' => $clarificationMessage
            ]);

            return;
        }

        // Шаг 3: Выполнение команды
        $result = $this->commandExecutor->execute($parsedCommand, $user);

        // Проверяем результат
        if ($result['success'] ?? false) {
            $command->markAsCompleted($result);
            $this->commandRepository->save($command);

            $this->notifyStatus($command, 'command_executed', $result);
        } else {
            $errorMessage = $result['message'] ?? 'Ошибка выполнения команды';
            $command->markAsFailed($errorMessage);
            $this->commandRepository->save($command);

            $this->notifyStatus($command, 'execution_failed', [
                'error' => $errorMessage
            ]);
        }
    }

    /**
     * Транскрипция аудио через Whisper
     *
     * @throws RuntimeException При ошибке транскрипции
     */
    private function transcribeAudio(string $audioUrl): TranscriptionResult
    {
        $this->logger->info('Transcribing audio', [
            'audio_url' => $audioUrl
        ]);

        try {
            // Загружаем аудио файл
            $audioData = $this->downloadAudio($audioUrl);

            // Отправляем на Whisper
            $response = $this->httpClient->request('POST', $this->whisperUrl . '/v1/audio/transcriptions', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data'
                ],
                'body' => [
                    'file' => $audioData,
                    'model' => 'whisper-1',
                    'language' => 'ru', // Приоритет русского языка
                    'response_format' => 'verbose_json'
                ],
                'timeout' => 60 // Увеличенный timeout для больших файлов
            ]);

            $data = $response->toArray();

            if (!isset($data['text'])) {
                throw new RuntimeException('Invalid Whisper response format');
            }

            // Создаем результат транскрипции
            return new TranscriptionResult(
                text: $data['text'],
                language: $data['language'] ?? 'ru',
                confidence: $data['confidence'] ?? 0.9,
                durationMs: isset($data['duration']) ? (int)($data['duration'] * 1000) : null
            );

        } catch (\Exception $e) {
            $this->logger->error('Transcription failed', [
                'audio_url' => $audioUrl,
                'error' => $e->getMessage()
            ]);

            // Fallback - создаем результат с низкой уверенностью
            throw new RuntimeException('Не удалось транскрибировать аудио: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Загрузка аудио файла
     */
    private function downloadAudio(string $audioUrl): string
    {
        try {
            $response = $this->httpClient->request('GET', $audioUrl);
            return $response->getContent();
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to download audio: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Обработка ошибок
     */
    private function handleError(VoiceCommand $command, \Exception $error): void
    {
        $this->logger->error('Voice command processing failed', [
            'command_id' => $command->getId(),
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);

        $command->markAsFailed($error->getMessage());
        $this->commandRepository->save($command);

        $this->notifyStatus($command, 'processing_failed', [
            'error' => $error->getMessage()
        ]);
    }

    /**
     * Уведомление через WebSocket
     */
    private function notifyStatus(VoiceCommand $command, string $event, array $data = []): void
    {
        try {
            $this->wsPublisher->publish(
                userId: $command->getUser()->getId(),
                event: $event,
                data: array_merge([
                    'command_id' => $command->getId(),
                    'status' => $command->getStatus()->value,
                    'type' => $command->getCommandType()->value,
                ], $data)
            );
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем основной процесс
            $this->logger->warning('Failed to send WebSocket notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Повторная обработка застрявших команд
     *
     * @return int Количество обработанных команд
     */
    public function retryStuckCommands(): int
    {
        $stuckCommands = $this->commandRepository->findStuckCommands(5);
        $count = 0;

        foreach ($stuckCommands as $command) {
            $this->logger->warning('Retrying stuck command', [
                'command_id' => $command->getId(),
                'status' => $command->getStatus()->value
            ]);

            try {
                // Если есть транскрибированный текст, продолжаем с него
                if ($command->getTranscribedText()) {
                    $this->processCommandText(
                        $command,
                        $command->getTranscribedText(),
                        $command->getUser()
                    );
                    $count++;
                } else {
                    // Иначе помечаем как проваленную
                    $command->markAsFailed('Command stuck without transcription');
                    $this->commandRepository->save($command);
                }
            } catch (\Exception $e) {
                $this->handleError($command, $e);
            }
        }

        return $count;
    }

    /**
     * Получение истории команд пользователя
     */
    public function getUserCommandHistory(User $user, int $limit = 20): array
    {
        $commands = $this->commandRepository->findByUser($user, null, $limit);

        return array_map(fn(VoiceCommand $cmd) => $cmd->toArray(), $commands);
    }

    /**
     * Получение статистики пользователя
     */
    public function getUserStatistics(User $user): array
    {
        return $this->commandRepository->getUserStatistics($user);
    }
}