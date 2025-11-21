<?php

declare(strict_types=1);

namespace App\Service\AI\Service;

use App\Enum\TaskStatus;
use Psr\Log\LoggerInterface;

/**
 * Маппер для статусов задач
 *
 * Преобразует текстовые значения статусов от LLM в enum TaskStatus.
 * Следует принципу Single Responsibility.
 */
class StatusMapper
{
    /**
     * Маппинг английских вариантов (стандарт LLM)
     */
    private const ENGLISH_MAP = [
        'pending'     => TaskStatus::PENDING,
        'in_progress' => TaskStatus::IN_PROGRESS,
        'completed'   => TaskStatus::COMPLETED,
        'cancelled'   => TaskStatus::CANCELLED,
    ];

    /**
     * Маппинг русских вариантов
     */
    private const RUSSIAN_MAP = [
        // Pending статусы
        'ожидание'      => TaskStatus::PENDING,
        'в ожидании'    => TaskStatus::PENDING,
        'запланировано' => TaskStatus::PENDING,
        'запланирована' => TaskStatus::PENDING,
        'не начата'     => TaskStatus::PENDING,
        'не начато'     => TaskStatus::PENDING,
        'новая'         => TaskStatus::PENDING,
        'новый'         => TaskStatus::PENDING,

        // In Progress статусы
        'в работе'     => TaskStatus::IN_PROGRESS,
        'в процессе'   => TaskStatus::IN_PROGRESS,
        'выполняется'  => TaskStatus::IN_PROGRESS,
        'в разработке' => TaskStatus::IN_PROGRESS,
        'активна'      => TaskStatus::IN_PROGRESS,
        'активная'     => TaskStatus::IN_PROGRESS,

        // Completed статусы
        'завершено' => TaskStatus::COMPLETED,
        'завершена' => TaskStatus::COMPLETED,
        'выполнено' => TaskStatus::COMPLETED,
        'выполнена' => TaskStatus::COMPLETED,
        'готово'    => TaskStatus::COMPLETED,
        'готова'    => TaskStatus::COMPLETED,
        'закрыто'   => TaskStatus::COMPLETED,
        'закрыта'   => TaskStatus::COMPLETED,

        // Cancelled статусы
        'отменено'  => TaskStatus::CANCELLED,
        'отменена'  => TaskStatus::CANCELLED,
        'отменён'   => TaskStatus::CANCELLED,
        'отменен'   => TaskStatus::CANCELLED,
        'отмена'    => TaskStatus::CANCELLED,
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Преобразовать текстовое значение в TaskStatus
     *
     * @param string|null $status Текстовое значение статуса
     *
     * @return TaskStatus|null Enum статуса или null если не удалось определить
     */
    public function map(?string $status): ?TaskStatus
    {
        if (empty($status)) {
            return null;
        }

        $normalized = mb_strtolower(trim($status));

        // Сначала проверяем стандартные английские варианты
        if (isset(self::ENGLISH_MAP[$normalized])) {
            return self::ENGLISH_MAP[$normalized];
        }

        // Затем русские варианты
        if (isset(self::RUSSIAN_MAP[$normalized])) {
            return self::RUSSIAN_MAP[$normalized];
        }

        // Логируем неизвестное значение
        $this->logger->warning('Unknown status value', [
            'status'     => $status,
            'normalized' => $normalized,
        ]);

        return null;
    }

    /**
     * Преобразовать текстовое значение в TaskStatus или использовать значение по умолчанию
     *
     * @param string|null $status  Текстовое значение статуса
     * @param TaskStatus  $default Значение по умолчанию
     *
     * @return TaskStatus Enum статуса
     */
    public function mapOrDefault(?string $status, TaskStatus $default = TaskStatus::PENDING): TaskStatus
    {
        return $this->map($status) ?? $default;
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
            array_keys(self::RUSSIAN_MAP),
        );
    }

    /**
     * Проверить, поддерживается ли значение
     *
     * @param string $status Текстовое значение
     *
     * @return bool True если значение поддерживается
     */
    public function isSupported(string $status): bool
    {
        $normalized = mb_strtolower(trim($status));

        return isset(self::ENGLISH_MAP[$normalized]) || isset(self::RUSSIAN_MAP[$normalized]);
    }

    /**
     * Получить маппинг для конкретного языка
     *
     * @param string $language 'en' или 'ru'
     *
     * @return array<string, TaskStatus>
     */
    public function getMappingForLanguage(string $language): array
    {
        return match ($language) {
            'en'    => self::ENGLISH_MAP,
            'ru'    => self::RUSSIAN_MAP,
            default => array_merge(self::ENGLISH_MAP, self::RUSSIAN_MAP),
        };
    }
}
