<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Enum\TaskPriority;
use App\Entity\Enum\TaskStatus;
use App\Service\EnumTranslatorService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/enums', name: 'enum_')]
#[IsGranted('ROLE_USER')]
#[OA\Tag(name: 'Enums')]
class EnumController extends AbstractController
{
    public function __construct(
        private readonly EnumTranslatorService $enumTranslator,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/priorities', name: 'priorities', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get all task priorities with translations',
        description: 'Returns all available task priorities with translated labels based on Accept-Language header',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of task priorities',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'value', type: 'string', example: 'high'),
                            new OA\Property(property: 'label', type: 'string', example: 'High'),
                            new OA\Property(property: 'color', type: 'string', example: '#F59E0B'),
                            new OA\Property(property: 'icon', type: 'string', example: 'pi pi-chevron-up'),
                        ]
                    )
                )
            )
        ]
    )]
    public function getPriorities(Request $request): JsonResponse
    {
        $locale = $request->getLocale();
        $priorities = [];

        foreach (TaskPriority::cases() as $priority) {
            $priorities[] = [
                'value' => $priority->value,
                'label' => $this->enumTranslator->translatePriority($priority, $locale),
                'color' => $priority->getColor(),
                'icon' => $priority->getIcon(),
            ];
        }

        return $this->json($priorities);
    }

    #[Route('/statuses', name: 'statuses', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get all task statuses with translations',
        description: 'Returns all available task statuses with translated labels based on Accept-Language header',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of task statuses',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'value', type: 'string', example: 'completed'),
                            new OA\Property(property: 'label', type: 'string', example: 'Completed'),
                            new OA\Property(property: 'color', type: 'string', example: '#10B981'),
                            new OA\Property(property: 'icon', type: 'string', example: 'pi pi-check'),
                        ]
                    )
                )
            )
        ]
    )]
    public function getStatuses(Request $request): JsonResponse
    {
        $locale = $request->getLocale();
        $statuses = [];

        foreach (TaskStatus::cases() as $status) {
            $statuses[] = [
                'value' => $status->value,
                'label' => $this->enumTranslator->translateStatus($status, $locale),
                'color' => $status->getColor(),
                'icon' => $status->getIcon(),
            ];
        }

        return $this->json($statuses);
    }
}
