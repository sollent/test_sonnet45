<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\Task\CreateTaskDto;
use App\Dto\Request\Task\TaskFilterDto;
use App\Dto\Request\Task\UpdateTaskDto;
use App\Dto\Response\Task\TaskResponseDto;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\Database\TaskRepository;
use App\Service\TaskService;
use App\Service\TranslationService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\User;
use App\Repository\Database\UserRepository;

#[Route('/api/tasks', name: 'task_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepository $taskRepository,
        private readonly UserRepository $userRepository,
        private readonly TranslationService $translationService
    ) {
    }
    
    /**
     * Enrich task DTO with translations (including subtasks)
     */
    private function enrichDtoWithTranslations(TaskResponseDto $dto, Request $request): TaskResponseDto
    {
        $locale = $request->getLocale();
        
        $dto->priorityLabel = $this->translationService->translatePriority($dto->priority, $locale);
        $dto->statusLabel = $this->translationService->translateStatus($dto->status, $locale);
        
        // Recursively enrich subtasks
        if (!empty($dto->subtasks)) {
            foreach ($dto->subtasks as $subtask) {
                $this->enrichDtoWithTranslations($subtask, $request);
            }
        }
        
        return $dto;
    }
    
    /**
     * Enrich multiple task DTOs with translations
     */
    private function enrichDtosWithTranslations(array $dtos, Request $request): array
    {
        foreach ($dtos as $dto) {
            $this->enrichDtoWithTranslations($dto, $request);
        }
        return $dtos;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get list of tasks with filters',
        parameters: [
            new OA\Parameter(
                name: 'view',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'overdue', 'upcoming', 'all', 'unscheduled'])
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'tags',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer'))
            ),
            new OA\Parameter(
                name: 'completed',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'dateFrom',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'dateTo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'priorities',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
            ),
            new OA\Parameter(
                name: 'statuses',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tasks',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TaskResponseDto::class))
                )
            )
        ]
    )]
    public function list(
        Request $request,
        #[MapQueryString] TaskFilterDto $filters = new TaskFilterDto()
    ): JsonResponse {
        $user = $this->getUser();
        
        // Handle search
        $search = $request->query->get('search');
        if ($search) {
            $tasks = $this->taskService->searchTasks($user, $search, $filters);
        }
        // Handle view filters
        else {
            $view = $request->query->get('view', 'all');
            
            $tasks = match ($view) {
                'today' => $this->taskService->getTodayTasks($user, $filters),
                'overdue' => $this->taskService->getOverdueTasksPaginated($user, 1, 50, $filters)['tasks'],
                'upcoming' => $this->taskService->getUpcomingTasks($user, 30, $filters),
                'unscheduled' => $this->taskService->getUnscheduledTasksPaginated($user, 1, 50, $filters)['tasks'],
                default => $this->taskService->getActiveTasks($user, $filters)
            };
        }

        $response = array_map(
            fn(Task $task) => TaskResponseDto::fromEntity($task, false, false),
            $tasks
        );
        
        $response = $this->enrichDtosWithTranslations($response, $request);
        
        return $this->json($response);
    }

    #[Route('/overdue', name: 'overdue_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the paginated list of overdue tasks for the current user',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(ref: new Model(type: TaskResponseDto::class))),
                new OA\Property(property: 'total', type: 'integer')
            ]
        )
    )]
    #[OA\Parameter(name: 'page', in: 'query', description: 'The page number', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'The number of items per page', schema: new OA\Schema(type: 'integer', default: 20))]
    public function getOverdueTasks(
        Request $request,
        #[MapQueryString] TaskFilterDto $filters = new TaskFilterDto()
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $data = $this->taskService->getOverdueTasksPaginated($user, $page, $limit, $filters);
        $data['tasks'] = array_map(
            fn(Task $task) => TaskResponseDto::fromEntity($task, false, false),
            $data['tasks']
        );
        $data['tasks'] = $this->enrichDtosWithTranslations($data['tasks'], $request);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/unscheduled', name: 'unscheduled_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the paginated list of tasks without due dates for the current user',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(ref: new Model(type: TaskResponseDto::class))),
                new OA\Property(property: 'total', type: 'integer')
            ]
        )
    )]
    #[OA\Parameter(name: 'page', in: 'query', description: 'The page number', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'The number of items per page', schema: new OA\Schema(type: 'integer', default: 20))]
    public function getUnscheduledTasks(
        Request $request,
        #[MapQueryString] TaskFilterDto $filters = new TaskFilterDto()
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $data = $this->taskService->getUnscheduledTasksPaginated($user, $page, $limit, $filters);
        $data['tasks'] = array_map(
            fn(Task $task) => TaskResponseDto::fromEntity($task, false, false),
            $data['tasks']
        );
        $data['tasks'] = $this->enrichDtosWithTranslations($data['tasks'], $request);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/statistics', name: 'api_tasks_statistics', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get task statistics',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'pending', type: 'integer'),
                        new OA\Property(property: 'in_progress', type: 'integer'),
                        new OA\Property(property: 'completed', type: 'integer'),
                        new OA\Property(property: 'cancelled', type: 'integer'),
                        new OA\Property(property: 'overdue', type: 'integer')
                    ]
                )
            )
        ]
    )]
    public function statistics(): JsonResponse
    {
        $stats = $this->taskService->getTaskStatistics($this->getUser());
        
        return $this->json($stats);
    }

    #[Route('/reorder', name: 'api_tasks_reorder', methods: ['POST'])]
    #[OA\Post(
        summary: 'Reorder tasks',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'taskIds',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Tasks reordered')
        ]
    )]
    public function reorder(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $taskIds = $data['taskIds'] ?? [];
        
        $this->taskService->updateTaskSortOrders($this->getUser(), $taskIds);
        
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_task_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get single task',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task details',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $task = $this->taskRepository->findWithSubtasks($id, $user);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }
        
        $dto = TaskResponseDto::fromEntity($task, true);
        $this->enrichDtoWithTranslations($dto, $request);
        
        return $this->json($dto);
    }

    #[Route('', name: 'api_task_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create new task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTaskDto::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 400, description: 'Invalid input')
        ]
    )]
    public function create(#[MapRequestPayload] CreateTaskDto $dto, Request $request): JsonResponse
    {
        $task = $this->taskService->createTask($dto, $this->getUser());
        
        $responseDto = TaskResponseDto::fromEntity($task, true);
        $this->enrichDtoWithTranslations($responseDto, $request);
        
        return $this->json($responseDto, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_task_update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(
        summary: 'Update task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateTaskDto::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function update(Task $task, #[MapRequestPayload] UpdateTaskDto $dto, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $updatedTask = $this->taskService->updateTask($task, $dto, $this->getUser());
        
        $responseDto = TaskResponseDto::fromEntity($updatedTask, true);
        $this->enrichDtoWithTranslations($responseDto, $request);
        
        return $this->json($responseDto);
    }

    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete task',
        responses: [
            new OA\Response(response: 204, description: 'Task deleted'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function delete(Task $task): Response
    {
        $this->denyAccessUnlessGranted('delete', $task);
        
        $this->taskService->deleteTask($task, $this->getUser());
        
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/complete', name: 'api_task_complete', methods: ['POST'])]
    #[OA\Post(
        summary: 'Mark task as completed',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task completed',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function complete(Task $task, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $completedTask = $this->taskService->completeTask($task, $this->getUser());
        
        $dto = TaskResponseDto::fromEntity($completedTask, true);
        $this->enrichDtoWithTranslations($dto, $request);
        
        return $this->json($dto);
    }

    #[Route('/{id}/toggle', name: 'api_task_toggle', methods: ['POST'])]
    #[OA\Post(
        summary: 'Toggle task completion',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task status toggled',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function toggle(Task $task, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $toggledTask = $this->taskService->toggleTaskCompletion($task, $this->getUser());
        
        $dto = TaskResponseDto::fromEntity($toggledTask, true);
        $this->enrichDtoWithTranslations($dto, $request);
        
        return $this->json($dto);
    }

    #[Route('/{id}/archive', name: 'api_task_archive', methods: ['POST'])]
    #[OA\Post(
        summary: 'Archive task',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task archived',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function archive(Task $task, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $archivedTask = $this->taskService->archiveTask($task, $this->getUser());
        
        $dto = TaskResponseDto::fromEntity($archivedTask, true);
        $this->enrichDtoWithTranslations($dto, $request);
        
        return $this->json($dto);
    }

    #[Route('/{id}/unarchive', name: 'api_task_unarchive', methods: ['POST'])]
    #[OA\Post(
        summary: 'Unarchive task',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task unarchived',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function unarchive(Task $task, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $unarchivedTask = $this->taskService->unarchiveTask($task, $this->getUser());
        
        $dto = TaskResponseDto::fromEntity($unarchivedTask, true);
        $this->enrichDtoWithTranslations($dto, $request);
        
        return $this->json($dto);
    }

    #[Route('/calendar/month', name: 'api_tasks_calendar_month', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get tasks for calendar month view',
        parameters: [
            new OA\Parameter(name: 'year', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'month', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'includeCompleted', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks for month',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TaskResponseDto::class))
                )
            )
        ]
    )]
    public function calendarMonth(Request $request): JsonResponse
    {
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('m'));
        $includeCompleted = $request->query->getBoolean('includeCompleted', true);
        
        $tasks = $this->taskRepository->findTasksForMonth($this->getUser(), $year, $month, $includeCompleted);
        
        $dtos = array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks);
        $dtos = $this->enrichDtosWithTranslations($dtos, $request);
        
        return $this->json($dtos);
    }

    #[Route('/calendar/week', name: 'api_tasks_calendar_week', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get tasks for calendar week view',
        parameters: [
            new OA\Parameter(name: 'weekStart', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'includeCompleted', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks for week',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TaskResponseDto::class))
                )
            )
        ]
    )]
    public function calendarWeek(Request $request): JsonResponse
    {
        $weekStartStr = $request->query->get('weekStart', (new \DateTime('monday this week'))->format('Y-m-d'));
        $weekStart = new \DateTime($weekStartStr);
        $includeCompleted = $request->query->getBoolean('includeCompleted', true);
        
        $tasks = $this->taskRepository->findTasksForWeek($this->getUser(), $weekStart, $includeCompleted);
        
        $dtos = array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks);
        $dtos = $this->enrichDtosWithTranslations($dtos, $request);
        
        return $this->json($dtos);
    }

    #[Route('/calendar/day', name: 'api_tasks_calendar_day', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get tasks for specific day',
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'includeCompleted', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks for day',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TaskResponseDto::class))
                )
            )
        ]
    )]
    public function calendarDay(Request $request): JsonResponse
    {
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $date = new \DateTime($dateStr);
        $includeCompleted = $request->query->getBoolean('includeCompleted', true);
        
        $tasks = $this->taskRepository->findTasksByDay($this->getUser(), $date, $includeCompleted);
        
        $dtos = array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks);
        $dtos = $this->enrichDtosWithTranslations($dtos, $request);
        
        return $this->json($dtos);
    }
}
