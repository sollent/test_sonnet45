<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Tag;

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
 * Команда удаления тега с задачи
 *
 * Обрабатывает действие remove_tag из ParsedCommand.
 * Следует принципу Single Responsibility.
 */
class RemoveTagCommand extends AbstractVoiceCommand
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
        return ParsedCommand::ACTION_REMOVE_TAG;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for removing tag');
        }

        if (empty($parameters['tag'])) {
            throw new RuntimeException('Tag name is required');
        }
    }

    protected function doExecute(array $parameters, User $user): CommandResponse
    {
        $search = $this->taskFinder->extractSearch($parameters);
        $tagName = $parameters['tag'];

        // Поиск задачи
        $task = $this->taskFinder->find($search, $user);

        if (!$task) {
            return $this->responseBuilder->taskNotFound($search);
        }

        // Ищем тег у задачи
        $tagToRemove = null;

        foreach ($task->getTags() as $tag) {
            if (mb_strtolower($tag->getName()) === mb_strtolower($tagName)) {
                $tagToRemove = $tag;
                break;
            }
        }

        if (!$tagToRemove) {
            return CommandResponse::failure(
                'tag_not_found',
                sprintf('Тег "%s" не найден у задачи "%s"', $tagName, $task->getTitle()),
            );
        }

        // Удаляем тег
        $task->removeTag($tagToRemove);
        $this->flush();

        return $this->responseBuilder->tagRemoved($task, $tagName);
    }
}
