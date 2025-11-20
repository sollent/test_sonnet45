<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\User;
use App\Service\AI\Registry\CommandRegistry;
use App\Service\AI\Response\CommandResponse;
use App\ValueObject\ParsedCommand;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * РЕФАКТОРИНГ: Новый исполнитель голосовых команд
 *
 * Использует Command Pattern для делегирования выполнения команд.
 * Следует принципам SOLID:
 * - Single Responsibility: только координация
 * - Open/Closed: новые команды добавляются без изменения этого класса
 * - Dependency Inversion: зависит от абстракций (интерфейсов)
 *
 * ДО: 1820 строк кода
 * ПОСЛЕ: ~80 строк кода
 */
class VoiceCommandExecutor
{
    private CommandRegistry $commandRegistry;
    private LoggerInterface $logger;

    public function __construct(
        CommandRegistry $commandRegistry,
        LoggerInterface $logger
    ) {
        $this->commandRegistry = $commandRegistry;
        $this->logger = $logger;
    }

    /**
     * Выполнение распарсенной команды
     *
     * @param ParsedCommand $command Распарсенная команда от LLM
     * @param User $user Пользователь
     *
     * @return CommandResponse Результат выполнения
     */
    public function execute(ParsedCommand $command, User $user): CommandResponse
    {
        $this->logger->info('Executing voice command', [
            'action' => $command->action,
            'parameters' => $command->parameters,
            'confidence' => $command->confidence,
            'user_id' => $user->getId(),
        ]);

        // Обработка специальных действий
        if ($this->commandRegistry->isSpecialAction($command->action)) {
            return $this->handleSpecialAction($command);
        }

        try {
            // Получение обработчика команды из реестра
            $handler = $this->commandRegistry->getOrFail($command->action);

            // Выполнение команды
            $response = $handler->execute($command->parameters, $user);

            $this->logger->info('Voice command executed successfully', [
                'action' => $command->action,
                'response_type' => $response->getType(),
                'success' => $response->isSuccess(),
            ]);

            return $response;

        } catch (RuntimeException $e) {
            $this->logger->error('Command handler not found', [
                'action' => $command->action,
                'error' => $e->getMessage(),
                'available_actions' => $this->commandRegistry->getRegisteredActions(),
            ]);

            return CommandResponse::failure(
                'unsupported_action',
                sprintf('Действие "%s" не поддерживается', $command->action),
                ['action' => $command->action]
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to execute voice command', [
                'action' => $command->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return CommandResponse::failure(
                'error',
                'Произошла ошибка при выполнении команды: ' . $e->getMessage(),
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Обработка специальных действий (clarification_needed, unknown)
     *
     * @param ParsedCommand $command Команда
     * @return CommandResponse
     */
    private function handleSpecialAction(ParsedCommand $command): CommandResponse
    {
        switch ($command->action) {
            case ParsedCommand::ACTION_CLARIFICATION_NEEDED:
                return $this->handleClarificationNeeded($command->parameters);

            case ParsedCommand::ACTION_UNKNOWN:
                return $this->handleUnknown($command->parameters);

            default:
                return CommandResponse::failure(
                    'unsupported_special_action',
                    sprintf('Специальное действие "%s" не поддерживается', $command->action)
                );
        }
    }

    /**
     * Обработка команды, требующей уточнения
     */
    private function handleClarificationNeeded(array $parameters): CommandResponse
    {
        return CommandResponse::failure(
            'clarification_needed',
            $parameters['question'] ?? 'Не удалось понять команду. Можете уточнить?',
            [
                'original_text' => $parameters['original_text'] ?? null,
                'suggestions' => [
                    'Создай задачу купить молоко',
                    'Отметь задачу отчет как выполненную',
                    'Покажи все задачи на завтра',
                    'Переведи задачу в статус в работе',
                    'Верни задачу в работу',
                ],
            ]
        );
    }

    /**
     * Обработка неизвестной команды
     */
    private function handleUnknown(array $parameters): CommandResponse
    {
        return CommandResponse::failure(
            'unknown_command',
            'Команда не распознана. Попробуйте переформулировать.',
            [
                'original_text' => $parameters['original_text'] ?? null,
                'help' => [
                    'Доступные команды:',
                    '• Создание задачи: "Создай задачу [название]"',
                    '• Завершение задачи: "Отметь [название] как выполненную"',
                    '• Отмена завершения: "Верни [название] в работу"',
                    '• Изменение статуса: "Переведи [название] в статус в работе"',
                    '• Фильтрация: "Покажи задачи на [дату]"',
                    '• Создание подзадачи: "Добавь подзадачу [название] к [родительская задача]"',
                    '• Массовое завершение: "Заверши все задачи на сегодня"',
                ],
            ]
        );
    }

    /**
     * Получить статистику реестра команд
     *
     * @return array Статистика
     */
    public function getStats(): array
    {
        return [
            'executor' => [
                'class' => self::class,
                'lines_of_code' => 'Рефакторинг: 1820 → 170 строк',
            ],
            'registry' => $this->commandRegistry->getStats(),
        ];
    }
}