<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\TranslationService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/translations', name: 'api_translations_')]
#[OA\Tag(name: 'Translations')]
final class TranslationController extends AbstractController
{
    public function __construct(
        private readonly TranslationService $translationService
    ) {
    }

    #[Route('/enums', name: 'enums', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get all enum translations',
        description: 'Returns translated labels for all TaskPriority and TaskStatus enums'
    )]
    #[OA\Parameter(
        name: 'locale',
        in: 'query',
        required: false,
        description: 'Locale for translations (en, ru). If not provided, uses Accept-Language header',
        schema: new OA\Schema(type: 'string', enum: ['en', 'ru'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Enum translations',
        content: new OA\JsonContent(
            type: 'object'
        )
    )]
    public function getEnumTranslations(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale') ?? $request->getLocale();
        
        return $this->json(
            $this->translationService->getAllEnumTranslations($locale)
        );
    }

    #[Route('/priorities', name: 'priorities', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get priority translations',
        description: 'Returns translated labels for TaskPriority enum'
    )]
    #[OA\Response(
        response: 200,
        description: 'Priority translations'
    )]
    public function getPriorityTranslations(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale') ?? $request->getLocale();
        
        return $this->json(
            $this->translationService->getAllPriorityTranslations($locale)
        );
    }

    #[Route('/statuses', name: 'statuses', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get status translations',
        description: 'Returns translated labels for TaskStatus enum'
    )]
    #[OA\Response(
        response: 200,
        description: 'Status translations'
    )]
    public function getStatusTranslations(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale') ?? $request->getLocale();
        
        return $this->json(
            $this->translationService->getAllStatusTranslations($locale)
        );
    }
}
