<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\VoiceCommand;
use App\Message\ProcessVoiceCommand;
use App\Repository\Database\UserRepository;
use App\Repository\Database\VoiceCommandRepository;
use App\Service\AI\VoiceProcessingService;
use App\Service\AI\WebSocketPublisher;
use App\ValueObject\CommandStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * Обработчик асинхронной обработки голосовых команд
 *
 * Обрабатывает команды из RabbitMQ очереди в фоновом режиме.
 * Использует паттерн Message Handler из Symfony Messenger
 */
#[AsMessageHandler]
final class ProcessVoiceCommandHandler
{
    public function __construct(
        private VoiceCommandRepository $commandRepository,
        private UserRepository $userRepository,
        private VoiceProcessingService $voiceProcessingService,
        private WebSocketPublisher $wsPublisher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Обработка сообщения из очереди
     */
    public function __invoke(ProcessVoiceCommand $message): void
    {
        $this->logger->info('Processing voice command from queue', [
            'command_id' => $message->getCommandId(),
            'user_id' => $message->getUserId(),
            'type' => $message->getType()
        ]);

        try {
            // Получаем команду из БД
            $command = $this->commandRepository->find($message->getCommandId());

            if (!$command) {
                throw new UnrecoverableMessageHandlingException(
                    sprintf('Voice command #%d not found', $message->getCommandId())
                );
            }

            // Проверяем статус (не обрабатываем уже завершенные)
            if ($command->isFinished()) {
                $this->logger->warning('Command already finished, skipping', [
                    'command_id' => $command->getId(),
                    'status' => $command->getStatus()->value
                ]);
                return;
            }

            // Получаем пользователя
            $user = $this->userRepository->find($message->getUserId());

            if (!$user) {
                throw new UnrecoverableMessageHandlingException(
                    sprintf('User #%d not found', $message->getUserId())
                );
            }

            // Уведомляем о начале обработки
            $this->wsPublisher->publishVoiceCommandStatus(
                $user->getId(),
                $command->getId(),
                'processing_async',
                ['message' => 'Обработка началась в фоновом режиме']
            );

            // Запускаем обработку в зависимости от типа
            if ($message->getType() === 'voice_audio' && $message->getAudioUrl()) {
                $this->voiceProcessingService->processVoiceAudio(
                    $message->getAudioUrl(),
                    $user
                );
            } elseif ($message->getType() === 'voice_text' && $message->getText()) {
                $this->voiceProcessingService->processVoiceText(
                    $message->getText(),
                    $user
                );
            } else {
                throw new UnrecoverableMessageHandlingException(
                    'Invalid command type or missing data'
                );
            }

            $this->logger->info('Voice command processed successfully', [
                'command_id' => $command->getId(),
                'status' => $command->getStatus()->value
            ]);

        } catch (UnrecoverableMessageHandlingException $e) {
            // Не повторяем обработку невосстановимых ошибок
            $this->logger->error('Unrecoverable error processing voice command', [
                'command_id' => $message->getCommandId(),
                'error' => $e->getMessage()
            ]);

            // Обновляем статус команды если она существует
            if (isset($command)) {
                $command->markAsFailed('Unrecoverable error: ' . $e->getMessage());
                $this->commandRepository->save($command);

                $this->wsPublisher->publishVoiceCommandStatus(
                    $message->getUserId(),
                    $command->getId(),
                    'failed_permanently',
                    ['error' => $e->getMessage()]
                );
            }

            throw $e;

        } catch (\Exception $e) {
            // Обычные ошибки - можно повторить
            $this->logger->error('Error processing voice command', [
                'command_id' => $message->getCommandId(),
                'error' => $e->getMessage()
            ]);

            // Обновляем статус команды
            if (isset($command)) {
                $attempt = $command->getMetadata()['retry_count'] ?? 0;
                $command->addMetadata('retry_count', $attempt + 1);
                $command->addMetadata('last_error', $e->getMessage());
                $this->commandRepository->save($command);

                $this->wsPublisher->publishVoiceCommandStatus(
                    $message->getUserId(),
                    $command->getId(),
                    'retry_scheduled',
                    [
                        'error' => $e->getMessage(),
                        'retry_count' => $attempt + 1
                    ]
                );
            }

            // Пробрасываем исключение для повторной обработки
            throw $e;
        }
    }
}