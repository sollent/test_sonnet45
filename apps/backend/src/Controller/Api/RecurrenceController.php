<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\Recurrence\CreateRecurrenceDto;
use App\Dto\Response\Recurrence\RecurrenceRuleDto;
use App\Entity\User;
use App\Repository\Database\RecurrenceRuleRepository;
use App\Repository\Database\TaskRepository;
use App\Service\RecurrenceService;
use DateTime;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/recurrence', name: 'api_recurrence_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Recurrence')]
class RecurrenceController extends AbstractController
{
    public function __construct(
        private readonly RecurrenceService $recurrenceService,
        private readonly RecurrenceRuleRepository $recurrenceRepository,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get user recurrence rules',
        description: 'Returns all recurrence rules for the current user',
    )]
    #[OA\Response(
        response: 200,
        description: 'List of recurrence rules',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: RecurrenceRuleDto::class)),
        ),
    )]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $rules = $this->recurrenceRepository->findActiveByUser($user);

        $dtos = array_map(function ($rule) {
            $previewDates = $this->recurrenceService->getPreviewDates(
                new DateTime(),
                $rule,
                5,
            );

            return RecurrenceRuleDto::fromEntity($rule, $previewDates);
        }, $rules);

        return $this->json($dtos);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get recurrence rule details',
        description: 'Returns details of a specific recurrence rule',
    )]
    #[OA\Response(
        response: 200,
        description: 'Recurrence rule details',
        content: new OA\JsonContent(ref: new Model(type: RecurrenceRuleDto::class)),
    )]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        $previewDates = $this->recurrenceService->getPreviewDates(
            new DateTime(),
            $rule,
            10,
        );

        return $this->json(RecurrenceRuleDto::fromEntity($rule, $previewDates));
    }

    #[Route('/task/{taskId}', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create recurrence rule for task',
        description: 'Creates a new recurrence rule for an existing task',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateRecurrenceDto::class)),
    )]
    #[OA\Response(
        response: 201,
        description: 'Recurrence rule created',
        content: new OA\JsonContent(ref: new Model(type: RecurrenceRuleDto::class)),
    )]
    #[OA\Response(response: 404, description: 'Task not found')]
    #[OA\Response(response: 400, description: 'Invalid recurrence configuration')]
    public function create(
        int $taskId,
        #[MapRequestPayload]
        CreateRecurrenceDto $dto,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $task = $this->taskRepository->find($taskId);

        if (!$task || $task->getUser() !== $user) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getRecurrenceRule()) {
            return $this->json(['error' => 'Task already has a recurrence rule'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $rule = $this->recurrenceService->createRecurrenceRule(
                $task,
                $dto->recurrenceType,
                $dto->toArray(),
            );

            $previewDates = $this->recurrenceService->getPreviewDates(
                new DateTime(),
                $rule,
                5,
            );

            return $this->json(
                RecurrenceRuleDto::fromEntity($rule, $previewDates),
                Response::HTTP_CREATED,
            );
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Update recurrence rule',
        description: 'Updates an existing recurrence rule',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateRecurrenceDto::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Recurrence rule updated',
        content: new OA\JsonContent(ref: new Model(type: RecurrenceRuleDto::class)),
    )]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function update(
        int $id,
        #[MapRequestPayload]
        CreateRecurrenceDto $dto,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $rule = $this->recurrenceService->updateRecurrenceRule($rule, $dto->toArray());

            $previewDates = $this->recurrenceService->getPreviewDates(
                new DateTime(),
                $rule,
                5,
            );

            return $this->json(RecurrenceRuleDto::fromEntity($rule, $previewDates));
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete recurrence rule',
        description: 'Deletes a recurrence rule and stops future task generation',
    )]
    #[OA\Response(response: 204, description: 'Recurrence rule deleted')]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function delete(int $id, #[CurrentUser] User $user): Response
    {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        $this->recurrenceService->deleteRecurrenceRule($rule);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/preview', name: 'preview', methods: ['GET'])]
    #[OA\Get(
        summary: 'Preview upcoming occurrences',
        description: 'Returns a preview of the next occurrences for a recurrence rule',
    )]
    #[OA\Parameter(
        name: 'count',
        in: 'query',
        description: 'Number of occurrences to preview',
        schema: new OA\Schema(type: 'integer', default: 5, minimum: 1, maximum: 20),
    )]
    #[OA\Response(
        response: 200,
        description: 'List of upcoming occurrence dates',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'dates',
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'date-time'),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function preview(
        int $id,
        Request $request,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        $count = min(20, max(1, (int) $request->query->get('count', 5)));

        $dates = $this->recurrenceService->getPreviewDates(
            new DateTime(),
            $rule,
            $count,
        );

        return $this->json([
            'dates' => array_map(fn ($date) => $date->format('c'), $dates),
        ]);
    }

    #[Route('/{id}/pause', name: 'pause', methods: ['POST'])]
    #[OA\Post(
        summary: 'Pause recurrence rule',
        description: 'Pauses a recurrence rule to stop generating tasks temporarily',
    )]
    #[OA\Response(response: 200, description: 'Recurrence rule paused')]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function pause(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        $rule->setIsActive(false);
        $this->recurrenceRepository->save($rule);

        return $this->json(['message' => 'Recurrence rule paused']);
    }

    #[Route('/{id}/resume', name: 'resume', methods: ['POST'])]
    #[OA\Post(
        summary: 'Resume recurrence rule',
        description: 'Resumes a paused recurrence rule to continue generating tasks',
    )]
    #[OA\Response(response: 200, description: 'Recurrence rule resumed')]
    #[OA\Response(response: 404, description: 'Recurrence rule not found')]
    public function resume(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $rule = $this->recurrenceRepository->find($id);

        if (!$rule || $rule->getCreatedBy() !== $user) {
            return $this->json(['error' => 'Recurrence rule not found'], Response::HTTP_NOT_FOUND);
        }

        $rule->setIsActive(true);

        // Recalculate next occurrence if needed
        if ($rule->getNextOccurrenceDate() < new DateTime()) {
            $nextOccurrence = $this->recurrenceService->calculateNextOccurrence(
                new DateTime(),
                $rule,
            );

            if ($nextOccurrence) {
                $rule->setNextOccurrenceDate($nextOccurrence);
            }
        }

        $this->recurrenceRepository->save($rule);

        return $this->json(['message' => 'Recurrence rule resumed']);
    }
}
