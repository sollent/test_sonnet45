<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Task;

use App\Entity\User;
use App\Service\AI\Command\Base\AbstractVoiceCommand;
use App\Service\AI\DateTimeParser;
use App\Service\AI\Response\CommandResponse;
use App\Service\AI\Response\ResponseBuilder;
use App\Service\AI\Service\TaskFinder;
use App\Service\AI\SmartSearchService;
use App\Service\TaskService;
use App\ValueObject\ParsedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Команда установки/изменения описания задачи
 *
 * Обрабатывает действие set_description из ParsedCommand.
 * Следует принципу Single Responsibility.
 */
class SetDescriptionCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private ResponseBuilder $responseBuilder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        ResponseBuilder $responseBuilder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->responseBuilder = $responseBuilder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_SET_DESCRIPTION;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for setting description');
        }

        if (!isset($parameters['description'])) {
            throw new RuntimeException('Description is required');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);
        $description = $parameters['description'];

        // Поиск задачи
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return $this->responseBuilder->taskNotFound($search);
        }

        // Устанавливаем описание
        $task->setDescription($description);
        $this->flush();

        return CommandResponse::success(
            'description_set',
            sprintf('Описание задачи "%s" обновлено', $task->getTitle()),
            [
                'task' => [
                    'id'          => $task->getId(),
                    'title'       => $task->getTitle(),
                    'description' => $description,
                ],
            ],
        );
    }
}
