<?php

declare(strict_types=1);

namespace App\Service\AI\Service;

use App\Enum\TaskPriority;
use Psr\Log\LoggerInterface;

/**
 * Маппер для приоритетов задач
 *
 * Преобразует текстовые значения приоритетов от LLM в enum TaskPriority.
 * Следует принципу Single Responsibility.
 */
class PriorityMapper
{
    private LoggerInterface $logger;

    /**
     * Маппинг английских вариантов (стандарт LLM)
     */
    private const ENGLISH_MAP = [
        'low'    => TaskPriority::LOW,
        'medium' => TaskPriority::MEDIUM,
        'high'   => TaskPriority::HIGH,
        'urgent' => TaskPriority::URGENT,
    ];

    /**
     * Маппинг русских вариантов (на случай если LLM вернет русские)
     */
    private const RUSSIAN_MAP = [
        'низкий'  => TaskPriority::LOW,
        'низкая'  => TaskPriority::LOW,
        'средний' => TaskPriority::MEDIUM,
        'средняя' => TaskPriority::MEDIUM,
        'обычный' => TaskPriority::MEDIUM,
        'обычная' => TaskPriority::MEDIUM,
        'высокий' => TaskPriority::HIGH,
        'высокая' => TaskPriority::HIGH,
        'важный'  => TaskPriority::HIGH,
        'важная'  => TaskPriority::HIGH,
        'срочный' => TaskPriority::URGENT,
        'срочная' => TaskPriority::URGENT,
        'критичный' => TaskPriority::URGENT,
        'критичная' => TaskPriority::URGENT,
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Преобразовать текстовое значение в TaskPriority
     *
     * @param string|null $priority Текстовое значение приоритета
     * @return TaskPriority Enum приоритета
     */
    public function map(?string $priority): TaskPriority
    {
        if (empty($priority)) {
            return TaskPriority::MEDIUM;
        }

        $normalized = mb_strtolower(trim($priority));

        // Сначала проверяем стандартные английские варианты
        if (isset(self::ENGLISH_MAP[$normalized])) {
            return self::ENGLISH_MAP[$normalized];
        }

        // Затем русские варианты
        if (isset(self::RUSSIAN_MAP[$normalized])) {
            return self::RUSSIAN_MAP[$normalized];
        }

        // Логируем неизвестное значение
        $this->logger->warning('Unknown priority value, defaulting to MEDIUM', [
            'priority' => $priority,
            'normalized' => $normalized,
        ]);

        return TaskPriority::MEDIUM;
    }

    /**
     * Получить все поддерживаемые значения
     *
     * @return array<string> Список всех поддерживаемых текстовых значений
     */
    public function getSupportedValues(): array
    {
        return array_merge(
            array_keys(self::ENGLISH_MAP),
            array_keys(self::RUSSIAN_MAP)
        );
    }

    /**
     * Проверить, поддерживается ли значение
     *
     * @param string $priority Текстовое значение
     * @return bool True если значение поддерживается
     */
    public function isSupported(string $priority): bool
    {
        $normalized = mb_strtolower(trim($priority));
        return isset(self::ENGLISH_MAP[$normalized]) || isset(self::RUSSIAN_MAP[$normalized]);
    }
}