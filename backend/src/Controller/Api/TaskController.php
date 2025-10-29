<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\Task\CreateTaskDto;
use App\Dto\Request\Task\UpdateTaskDto;
use App\Dto\Response\Task\TaskResponseDto;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\Database\TaskRepository;
use App\Service\TaskService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tasks')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepository $taskRepository
    ) {
    }

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get list of tasks',
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'])
            ),
            new OA\Parameter(
                name: 'archived',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'tag',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'view',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['today', 'overdue', 'upcoming', 'all'])
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
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Handle search
        $search = $request->query->get('search');
        if ($search) {
            $tasks = $this->taskService->searchTasks($user, $search);
        }
        // Handle tag filter
        elseif ($tagId = $request->query->getInt('tag')) {
            $tasks = $this->taskService->getTasksByTag($user, $tagId);
        }
        // Handle view filters
        else {
            $view = $request->query->get('view', 'all');
            
            $tasks = match ($view) {
                'today' => $this->taskService->getTodayTasks($user),
                'overdue' => $this->taskService->getOverdueTasks($user),
                'upcoming' => $this->taskService->getUpcomingTasks($user),
                default => $this->taskService->getUserTasks(
                    $user,
                    $request->query->has('status') ? TaskStatus::from($request->query->get('status')) : null,
                    $request->query->getBoolean('archived', false),
                    true
                )
            };
        }

        $response = array_map(
            fn($task) => TaskResponseDto::fromEntity($task, true),
            $tasks
        );

        return $this->json($response);
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
    public function show(int $id): JsonResponse
    {
        $task = $this->taskRepository->findWithSubtasks($id);
        
        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }
        
        $this->denyAccessUnlessGranted('view', $task);
        
        return $this->json(TaskResponseDto::fromEntity($task, true));
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
    public function create(#[MapRequestPayload] CreateTaskDto $dto): JsonResponse
    {
        $task = $this->taskService->createTask($dto, $this->getUser());
        
        return $this->json(
            TaskResponseDto::fromEntity($task, true),
            Response::HTTP_CREATED
        );
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
    public function update(Task $task, #[MapRequestPayload] UpdateTaskDto $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $updatedTask = $this->taskService->updateTask($task, $dto, $this->getUser());
        
        return $this->json(TaskResponseDto::fromEntity($updatedTask, true));
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
    public function complete(Task $task): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $completedTask = $this->taskService->completeTask($task, $this->getUser());
        
        return $this->json(TaskResponseDto::fromEntity($completedTask, true));
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
    public function toggle(Task $task): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $toggledTask = $this->taskService->toggleTaskCompletion($task, $this->getUser());
        
        return $this->json(TaskResponseDto::fromEntity($toggledTask, true));
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
    public function archive(Task $task): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $archivedTask = $this->taskService->archiveTask($task, $this->getUser());
        
        return $this->json(TaskResponseDto::fromEntity($archivedTask, true));
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
    public function unarchive(Task $task): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $task);
        
        $unarchivedTask = $this->taskService->unarchiveTask($task, $this->getUser());
        
        return $this->json(TaskResponseDto::fromEntity($unarchivedTask, true));
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
        
        return $this->json(array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks));
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
        
        return $this->json(array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks));
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
        
        return $this->json(array_map(fn($task) => TaskResponseDto::fromEntity($task, false), $tasks));
    }
}
