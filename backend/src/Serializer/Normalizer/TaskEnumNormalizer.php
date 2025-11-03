<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\EnumTranslatorService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Custom normalizer for Task enums to include translated labels
 * This adds a 'label' field with the translated enum value in API responses
 */
final readonly class TaskEnumNormalizer implements NormalizerInterface
{
    public function __construct(
        private EnumTranslatorService $enumTranslator,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param TaskPriority|TaskStatus $object
     * @param array<string, mixed> $context
     * @return array{value: string, label: string, color: string, icon: string}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        // Get current request locale
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();

        $data = [
            'value' => $object->value,
            'label' => $this->getTranslatedLabel($object, $locale),
            'color' => $object->getColor(),
            'icon' => $object->getIcon(),
        ];

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TaskPriority || $data instanceof TaskStatus;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TaskPriority::class => true,
            TaskStatus::class => true,
        ];
    }

    private function getTranslatedLabel(TaskPriority|TaskStatus $enum, ?string $locale): string
    {
        return match (true) {
            $enum instanceof TaskPriority => $this->enumTranslator->translatePriority($enum, $locale),
            $enum instanceof TaskStatus => $this->enumTranslator->translateStatus($enum, $locale),
        };
    }
}
