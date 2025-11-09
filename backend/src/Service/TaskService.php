<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\Task\CreateTaskDto;
use App\Dto\Request\Task\TaskFilterDto;
use App\Dto\Request\Task\UpdateTaskDto;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Exception\Task\TaskNotFoundException;
use App\Exception\Task\TaskAccessDeniedException;
use App\Dto\Request\Recurrence\CreateRecurrenceDto;
use App\Repository\Database\TagRepository;
use App\Repository\Database\TaskRepository;
use App\Repository\Database\MediaObjectRepository;
use App\Entity\MediaObject;
use App\Service\RecurrenceService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaObjectRepository $mediaObjectRepository,
        private readonly ?RecurrenceService $recurrenceService = null,
        private readonly ?TranslationService $translationService = null,
        private readonly ?RequestStack $requestStack = null
    ) {
    }

    /**
     * Enrich task DTO with translations
     */
    private function enrichDtoWithTranslations(\App\Dto\Response\Task\TaskResponseDto $dto): void
    {
        if ($this->translationService === null) {
            return;
        }
        
        // Get locale from current request
        $locale = null;
        if ($this->requestStack !== null) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request !== null) {
                $locale = $request->getLocale();
            }
        }
        
        // Add translations
        $dto->priorityLabel = $this->translationService->translatePriority($dto->priority, $locale);
        $dto->statusLabel = $this->translationService->translateStatus($dto->status, $locale);
    }

    /**
     * Create a new task
     */
    public function createTask(CreateTaskDto $dto, User $user): Task
    {
        $task = new Task();
        $task->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setStatus($dto->status)
            ->setPriority($dto->priority)
            ->setStartDate($dto->startDate ? new \DateTimeImmutable($dto->startDate) : null)
            ->setDueDate($dto->dueDate ? new \DateTimeImmutable($dto->dueDate) : null)
            ->setSortOrder($dto->sortOrder)
            ->setIsArchived($dto->isArchived)
            ->setUser($user);

        // Handle parent task
        if ($dto->parentTaskId !== null) {
            $parentTask = $this->taskRepository->find($dto->parentTaskId);
            if ($parentTask && $parentTask->getUser() === $user) {
                $task->setParentTask($parentTask);
            }
        }

        // Handle tags
        if (!empty($dto->tags)) {
            $tags = $this->tagRepository->findOrCreateByNames($dto->tags, $user);
            foreach ($tags as $tag) {
                $task->addTag($tag);
            }
        }

        // Handle media objects
        if (!empty($dto->mediaIds)) {
            error_log('Processing mediaIds: ' . json_encode($dto->mediaIds));
            foreach ($dto->mediaIds as $mediaId) {
                $mediaObject = $this->mediaObjectRepository->find($mediaId);
                if ($mediaObject && $mediaObject->getUploadedBy() === $user) {
                    error_log('Adding media object ' . $mediaId . ' to task');
                    $task->addMediaObject($mediaObject);
                } else {
                    error_log('Media object ' . $mediaId . ' not found or access denied');
                }
            }
        } else {
            error_log('No mediaIds provided in DTO');
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Handle recurrence if specified
        if ($dto->recurrence !== null && $this->recurrenceService !== null) {
            $this->recurrenceService->createRecurrenceRule($task, 
                $dto->recurrence['recurrenceType'] ?? 'daily',
                $dto->recurrence
            );
        }

        return $task;
    }

    /**
     * Update an existing task
     */
    public function updateTask(Task $task, UpdateTaskDto $dto, User $user): Task
    {
        $this->ensureUserCanModifyTask($task, $user);

        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $task->setDescription($dto->description);
        }

        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }

        if ($dto->priority !== null) {
            $task->setPriority($dto->priority);
        }

        if ($dto->startDate !== null) {
            $task->setStartDate(new \DateTimeImmutable($dto->startDate));
        }

        if ($dto->dueDate !== null) {
            $task->setDueDate(new \DateTimeImmutable($dto->dueDate));
        }

        if ($dto->sortOrder !== null) {
            $task->setSortOrder($dto->sortOrder);
        }

        if ($dto->isArchived !== null) {
            $task->setIsArchived($dto->isArchived);
        }

        // Handle tags update
        if ($dto->tags !== null) {
            // Remove all current tags
            foreach ($task->getTags() as $tag) {
                $task->removeTag($tag);
            }

            // Add new tags
            if (!empty($dto->tags)) {
                $tags = $this->tagRepository->findOrCreateByNames($dto->tags, $user);
                foreach ($tags as $tag) {
                    $task->addTag($tag);
                }
            }
        }

        // Handle media objects update
        if ($dto->mediaIds !== null) {
            // Clear existing media
            $task->clearMediaObjects();

            // Add new media
            if (!empty($dto->mediaIds)) {
                foreach ($dto->mediaIds as $mediaId) {
                    $mediaObject = $this->mediaObjectRepository->find($mediaId);
                    if ($mediaObject && $mediaObject->getUploadedBy() === $user) {
                        $task->addMediaObject($mediaObject);
                    }
                }
            }
        }

        $this->entityManager->flush();

        // Update tag usage counts
        $this->tagRepository->updateUsageCounts($user);

        return $task;
    }

    /**
     * Delete a task
     */
    public function deleteTask(Task $task, User $user): void
    {
        $this->ensureUserCanModifyTask($task, $user);

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        // Update tag usage counts
        $this->tagRepository->updateUsageCounts($user);
    }

    /**
     * Mark task as completed
     */
    public function completeTask(Task $task, User $user): Task
    {
        $this->ensureUserCanModifyTask($task, $user);

        $task->setStatus(TaskStatus::COMPLETED);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Toggle task completion status
     */
    public function toggleTaskCompletion(Task $task, User $user): Task
    {
        $this->ensureUserCanModifyTask($task, $user);

        if ($task->isCompleted()) {
            $task->setStatus(TaskStatus::PENDING);
        } else {
            $task->setStatus(TaskStatus::COMPLETED);

            // Automatically complete all subtasks when parent is completed
            $this->completeSubtasksRecursively($task);
        }

        $this->entityManager->flush();

        return $task;
    }

    /**
     * Complete all subtasks recursively
     */
    private function completeSubtasksRecursively(Task $task): void
    {
        foreach ($task->getSubtasks() as $subtask) {
            if (!$subtask->isCompleted()) {
                $subtask->setStatus(TaskStatus::COMPLETED);

                // Recursively complete nested subtasks
                $this->completeSubtasksRecursively($subtask);
            }
        }
    }

    /**
     * Archive a task
     */
    public function archiveTask(Task $task, User $user): Task
    {
        $this->ensureUserCanModifyTask($task, $user);

        $task->setIsArchived(true);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Unarchive a task
     */
    public function unarchiveTask(Task $task, User $user): Task
    {
        $this->ensureUserCanModifyTask($task, $user);

        $task->setIsArchived(false);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Get user's tasks with filters
     */
    public function getUserTasks(
        User $user,
        ?TaskStatus $status = null,
        bool $includeArchived = false,
        bool $onlyParentTasks = true
    ): array {
        return $this->taskRepository->findUserTasks(
            $user,
            $status,
            $includeArchived,
            $onlyParentTasks
        );
    }

    /**
     * Get today's tasks for user
     */
    public function getTodayTasks(User $user, ?TaskFilterDto $filters = null): array
    {
        return $this->taskRepository->findTodayTasks($user, $filters);
    }

    /**
     * Get overdue tasks for user
     */
    public function getOverdueTasks(User $user): array
    {
        return $this->taskRepository->findOverdueTasks($user);
    }

    /**
     * Get upcoming tasks for user
     */
    public function getUpcomingTasks(User $user, int $days = 7, ?TaskFilterDto $filters = null): array
    {
        return $this->taskRepository->findUpcomingTasks($user, $days, $filters);
    }

    public function getActiveTasks(User $user, ?TaskFilterDto $filters = null, ?int $limit = null, ?int $offset = null, bool $onlyWithSubtasks = false): array
    {
        return $this->taskRepository->findActiveTasks($user, $filters, $limit, $offset, $onlyWithSubtasks);
    }

    public function countActiveTasks(User $user, ?TaskFilterDto $filters = null): int
    {
        return $this->taskRepository->countActiveTasks($user, $filters);
    }

    public function getUnscheduledTasksPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null): array
    {
        $paginator = $this->taskRepository->findUnscheduledByUserPaginated($user, $page, $limit, $filters);

        $tasks = [];
        foreach ($paginator as $task) {
            $tasks[] = $task;
        }

        // Use separate count query for correct total (Paginator with fetchJoinCollection=false doesn't count correctly)
        $total = $this->taskRepository->countUnscheduledByUser($user, $filters);

        return [
            'tasks' => $tasks,
            'total' => $total,
        ];
    }

    /**
     * Search tasks by query
     */
    public function searchTasks(User $user, string $query, ?TaskFilterDto $filters = null): array
    {
        return $this->taskRepository->searchTasks($user, $query, $filters);
    }

    /**
     * Get tasks by tag
     */
    public function getTasksByTag(User $user, int $tagId): array
    {
        return $this->taskRepository->findTasksByTag($user, $tagId);
    }

    /**
     * Get task statistics for user
     */
    public function getTaskStatistics(User $user): array
    {
        return $this->taskRepository->getUserTaskStatistics($user);
    }

    /**
     * Update task sort orders
     */
    public function updateTaskSortOrders(User $user, array $taskIds): void
    {
        $sortOrder = 0;
        foreach ($taskIds as $taskId) {
            $task = $this->taskRepository->find($taskId);
            if ($task && $task->getUser() === $user) {
                $task->setSortOrder($sortOrder++);
            }
        }
        
        $this->entityManager->flush();
    }

    public function getOverdueTasksPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null): array
    {
        $paginator = $this->taskRepository->findOverdueByUserPaginated($user, $page, $limit, $filters);

        $tasks = [];
        foreach ($paginator as $task) {
            $tasks[] = $task;
        }

        // Use separate count query for correct total (Paginator with fetchJoinCollection=false doesn't count correctly)
        $total = $this->taskRepository->countOverdueByUser($user, $filters);

        return [
            'tasks' => $tasks,
            'total' => $total,
        ];
    }

    /**
     * Ensure user can modify the task
     * 
     * @throws TaskAccessDeniedException
     */
    private function ensureUserCanModifyTask(Task $task, User $user): void
    {
        if ($task->getUser() !== $user) {
            throw new TaskAccessDeniedException('You do not have permission to modify this task.');
        }
    }
}
