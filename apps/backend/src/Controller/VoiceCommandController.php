<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\VoiceCommandRequest;
use App\Dto\Response\VoiceCommandHistoryResponse;
use App\Dto\Response\VoiceCommandResponse;
use App\Dto\Response\VoiceCommandStatisticsResponse;
use App\Entity\User;
use App\Repository\Database\VoiceCommandRepository;
use App\Service\AI\VoiceProcessingService;
use App\ValueObject\CommandStatus;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ValueError;

/**
 * API контроллер для голосовых команд
 *
 * Предоставляет эндпоинты для обработки голосовых и текстовых команд,
 * получения истории и статистики использования.
 */
#[Route('/api/voice')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Voice Commands', description: 'Voice AI Assistant endpoints')]
class VoiceCommandController extends AbstractController
{
    public function __construct(
        private VoiceProcessingService $voiceProcessingService,
        private VoiceCommandRepository $commandRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Обработка голосовой или текстовой команды
     */
    #[Route('/command', name: 'api_voice_command', methods: ['POST'])]
    #[OA\Post(
        summary: 'Process voice or text command',
        description: 'Processes a voice command (audio URL) or text command for task management',
        requestBody: new OA\RequestBody(
            description: 'Voice command data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: VoiceCommandRequest::class)),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Command processed successfully',
                content: new OA\JsonContent(ref: new Model(type: VoiceCommandResponse::class)),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request data',
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
            ),
            new OA\Response(
                response: 500,
                description: 'Processing error',
            ),
        ],
    )]
    #[Security(name: 'Bearer')]
    public function processCommand(
        #[MapRequestPayload]
        VoiceCommandRequest $request,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        try {
            // Обработка в зависимости от типа команды
            if ($request->audioUrl !== null) {
                // Голосовая команда (аудио)
                $command = $this->voiceProcessingService->processVoiceAudio(
                    $request->audioUrl,
                    $user,
                );
            } elseif ($request->text !== null) {
                // Текстовая команда
                $command = $this->voiceProcessingService->processVoiceText(
                    $request->text,
                    $user,
                );
            } else {
                return $this->json([
                    'success' => false,
                    'error'   => 'Either audioUrl or text must be provided',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Формируем ответ
            $response = VoiceCommandResponse::fromEntity($command);

            return $this->json($response, Response::HTTP_OK);

        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Failed to process command: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Получение истории голосовых команд пользователя
     */
    #[Route('/history', name: 'api_voice_history', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get voice commands history',
        description: 'Returns the history of voice commands for the authenticated user',
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of commands to return',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100),
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                description: 'Number of commands to skip',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by status',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['pending', 'processing', 'executing', 'completed', 'failed'],
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commands history retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: VoiceCommandHistoryResponse::class)),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
            ),
        ],
    )]
    #[Security(name: 'Bearer')]
    public function getHistory(
        Request $request,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $limit = min((int) $request->query->get('limit', 20), 100);
        $offset = max((int) $request->query->get('offset', 0), 0);
        $statusFilter = $request->query->get('status');

        // Валидация статуса если указан
        $status = null;

        if ($statusFilter !== null) {
            try {
                $status = CommandStatus::from($statusFilter);
            } catch (ValueError $e) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Invalid status value',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Получаем команды из репозитория
        $commands = $this->commandRepository->findByUser($user, $status, $limit, $offset);

        // Формируем ответ
        $response = new VoiceCommandHistoryResponse();
        $response->commands = array_map(
            fn ($cmd) => VoiceCommandResponse::fromEntity($cmd),
            $commands,
        );
        $response->total = count($commands);
        $response->limit = $limit;
        $response->offset = $offset;

        return $this->json($response);
    }

    /**
     * Получение статуса конкретной команды
     */
    #[Route('/status/{id}', name: 'api_voice_status', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get voice command status',
        description: 'Returns the current status and details of a specific voice command',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Command ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Command status retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: VoiceCommandResponse::class)),
            ),
            new OA\Response(
                response: 404,
                description: 'Command not found',
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied to this command',
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
            ),
        ],
    )]
    #[Security(name: 'Bearer')]
    public function getStatus(
        int $id,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $command = $this->commandRepository->find($id);

        if (!$command) {
            return $this->json([
                'success' => false,
                'error'   => 'Command not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Проверяем доступ (только владелец может просматривать)
        if ($command->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error'   => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $response = VoiceCommandResponse::fromEntity($command);

        return $this->json($response);
    }

    /**
     * Получение статистики использования голосовых команд
     */
    #[Route('/statistics', name: 'api_voice_statistics', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get voice commands statistics',
        description: 'Returns usage statistics for voice commands',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: VoiceCommandStatisticsResponse::class)),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
            ),
        ],
    )]
    #[Security(name: 'Bearer')]
    public function getStatistics(
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $stats = $this->commandRepository->getUserStatistics($user);

        $response = new VoiceCommandStatisticsResponse();
        $response->totalCommands = $stats['total'];
        $response->commandsByStatus = $stats['by_status'];
        $response->averageDurationMs = $stats['average_duration_ms'];

        // Добавляем процентные показатели
        if ($stats['total'] > 0) {
            $response->successRate = round(
                (($stats['by_status']['completed'] ?? 0) / $stats['total']) * 100,
                2,
            );
        } else {
            $response->successRate = 0;
        }

        return $this->json($response);
    }

    /**
     * Повторная обработка проваленной команды
     */
    #[Route('/retry/{id}', name: 'api_voice_retry', methods: ['POST'])]
    #[OA\Post(
        summary: 'Retry failed command',
        description: 'Attempts to reprocess a failed voice command',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Command ID to retry',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Command reprocessing started',
                content: new OA\JsonContent(ref: new Model(type: VoiceCommandResponse::class)),
            ),
            new OA\Response(
                response: 404,
                description: 'Command not found',
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied or command cannot be retried',
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
            ),
        ],
    )]
    #[Security(name: 'Bearer')]
    public function retryCommand(
        int $id,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $command = $this->commandRepository->find($id);

        if (!$command) {
            return $this->json([
                'success' => false,
                'error'   => 'Command not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Проверяем доступ
        if ($command->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error'   => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        // Проверяем статус (можно повторить только проваленные)
        if ($command->getStatus() !== CommandStatus::FAILED) {
            return $this->json([
                'success' => false,
                'error'   => 'Only failed commands can be retried',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Сбрасываем статус и повторяем обработку
            $command->startProcessing();
            $this->commandRepository->save($command);

            // Запускаем повторную обработку
            if ($command->getTranscribedText()) {
                $this->voiceProcessingService->processVoiceText(
                    $command->getTranscribedText(),
                    $user,
                );
            } elseif ($command->getRawAudioUrl()) {
                $this->voiceProcessingService->processVoiceAudio(
                    $command->getRawAudioUrl(),
                    $user,
                );
            }

            $response = VoiceCommandResponse::fromEntity($command);

            return $this->json($response);

        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Failed to retry command: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
