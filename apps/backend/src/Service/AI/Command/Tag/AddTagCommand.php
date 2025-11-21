<?php

declare(strict_types=1);

namespace App\Service\AI\Command\Tag;

use App\Entity\User;
use App\Repository\Database\TagRepository;
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
 * Команда добавления тега к задаче
 *
 * Обрабатывает действие add_tag из ParsedCommand.
 * Создает тег если его не существует.
 * Следует принципу Single Responsibility.
 */
class AddTagCommand extends AbstractVoiceCommand
{
    private TaskFinder $taskFinder;

    private TagRepository $tagRepository;

    private ResponseBuilder $responseBuilder;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        SmartSearchService $searchService,
        DateTimeParser $dateTimeParser,
        LoggerInterface $logger,
        TaskFinder $taskFinder,
        TagRepository $tagRepository,
        ResponseBuilder $responseBuilder,
    ) {
        parent::__construct($entityManager, $taskService, $searchService, $dateTimeParser, $logger);
        $this->taskFinder = $taskFinder;
        $this->tagRepository = $tagRepository;
        $this->responseBuilder = $responseBuilder;
    }

    public function getAction(): string
    {
        return ParsedCommand::ACTION_ADD_TAG;
    }

    protected function validateParameters(array $parameters): void
    {
        $search = $this->taskFinder->extractSearch($parameters);

        if (empty($search)) {
            throw new RuntimeException('Search query is required for adding tag');
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

        // Найти или создать тег
        $tags = $this->tagRepository->findOrCreateByNames([$tagName], $user);
        $tag = $tags[0] ?? null;

        if (!$tag) {
            return CommandResponse::failure(
                'tag_creation_failed',
                sprintf('Не удалось создать тег "%s"', $tagName),
            );
        }

        // Проверяем, есть ли уже этот тег
        if ($task->getTags()->contains($tag)) {
            return CommandResponse::failure(
                'tag_already_exists',
                sprintf('Тег "%s" уже добавлен к задаче "%s"', $tagName, $task->getTitle()),
            );
        }

        // Добавляем тег
        $task->addTag($tag);
        $this->flush();

        return $this->responseBuilder->tagAdded($task, $tagName);
    }
}
