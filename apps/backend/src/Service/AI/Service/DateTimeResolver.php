<?php

declare(strict_types=1);

namespace App\Service\AI\Service;

use App\Service\AI\DateTimeParser;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Сервис для разрешения дат и времени из параметров команд
 *
 * Централизует логику парсинга дат, устраняя дублирование кода.
 * Следует принципу Single Responsibility.
 */
class DateTimeResolver
{
    private DateTimeParser $dateTimeParser;

    public function __construct(DateTimeParser $dateTimeParser)
    {
        $this->dateTimeParser = $dateTimeParser;
    }

    /**
     * Парсинг диапазона дат из параметров
     *
     * @param array $parameters Параметры с due_date, start_time, end_time
     *
     * @return array{start: ?DateTimeImmutable, due: ?DateTimeImmutable}
     */
    public function resolveDateRange(array $parameters): array
    {
        if (!isset($parameters['due_date'])) {
            return ['start' => null, 'due' => null];
        }

        // Временной диапазон (с 14:00 до 15:00)
        if ($this->hasTimeRange($parameters)) {
            return $this->resolveTimeRange($parameters);
        }

        // Только начальное время
        if ($this->hasStartTime($parameters)) {
            return $this->resolveStartTime($parameters);
        }

        // Только дата
        return $this->resolveDateOnly($parameters);
    }

    /**
     * Парсинг одной даты
     *
     * @param string $date Дата в стандартном формате LLM
     */
    public function resolveDate(string $date): ?DateTimeImmutable
    {
        return $this->dateTimeParser->parseStartDate($date);
    }

    /**
     * Парсинг даты для окончания дня
     *
     * @param string $date Дата в стандартном формате LLM
     */
    public function resolveDueDate(string $date): ?DateTimeImmutable
    {
        return $this->dateTimeParser->parseDueDate($date);
    }

    /**
     * Парсинг даты с временем
     *
     * @param string $date Дата
     * @param string $time Время в формате HH:MM
     */
    public function resolveDateWithTime(string $date, string $time): ?DateTimeImmutable
    {
        return $this->dateTimeParser->parseDateWithTime($date, $time);
    }

    /**
     * Определить период для cleanup_completed
     *
     * @param string      $period     Период (yesterday, last_week, last_month, before_date)
     * @param string|null $beforeDate Дата для before_date
     *
     * @return array{start: ?DateTimeImmutable, end: DateTimeImmutable}
     */
    public function resolvePeriod(string $period, ?string $beforeDate = null): array
    {
        $now = new DateTimeImmutable();

        switch ($period) {
            case 'yesterday':
                return [
                    'start' => $now->modify('-1 day')->setTime(0, 0, 0),
                    'end'   => $now->modify('-1 day')->setTime(23, 59, 59),
                ];

            case 'last_week':
                return [
                    'start' => $now->modify('-7 days')->setTime(0, 0, 0),
                    'end'   => $now->modify('-1 day')->setTime(23, 59, 59),
                ];

            case 'last_month':
                return [
                    'start' => $now->modify('-30 days')->setTime(0, 0, 0),
                    'end'   => $now->modify('-1 day')->setTime(23, 59, 59),
                ];

            case 'before_date':
                if (empty($beforeDate)) {
                    throw new InvalidArgumentException('before_date parameter is required for period=before_date');
                }
                $endDate = new DateTimeImmutable($beforeDate);

                return [
                    'start' => null, // Нет ограничения снизу
                    'end'   => $endDate->setTime(23, 59, 59),
                ];

            default:
                throw new InvalidArgumentException(sprintf(
                    'Invalid period: %s. Valid values: yesterday, last_week, last_month, before_date',
                    $period,
                ));
        }
    }

    /**
     * Проверка наличия временного диапазона
     */
    private function hasTimeRange(array $parameters): bool
    {
        return isset($parameters['start_time']) && isset($parameters['end_time']);
    }

    /**
     * Проверка наличия начального времени
     */
    private function hasStartTime(array $parameters): bool
    {
        return isset($parameters['start_time']) && !isset($parameters['end_time']);
    }

    /**
     * Разрешение временного диапазона
     */
    private function resolveTimeRange(array $parameters): array
    {
        $startDate = $this->dateTimeParser->parseDateWithTime(
            $parameters['due_date'],
            $parameters['start_time'],
        );
        $endDate = $this->dateTimeParser->parseDateWithTime(
            $parameters['due_date'],
            $parameters['end_time'],
        );

        return ['start' => $startDate, 'due' => $endDate];
    }

    /**
     * Разрешение только начального времени
     */
    private function resolveStartTime(array $parameters): array
    {
        $startDate = $this->dateTimeParser->parseDateWithTime(
            $parameters['due_date'],
            $parameters['start_time'],
        );
        $endDate = $startDate?->modify('+1 hour');

        return ['start' => $startDate, 'due' => $endDate];
    }

    /**
     * Разрешение только даты
     */
    private function resolveDateOnly(array $parameters): array
    {
        $startDate = $this->dateTimeParser->parseStartDate($parameters['due_date']);
        $dueDate = $this->dateTimeParser->parseDueDate($parameters['due_date']);

        return ['start' => $startDate, 'due' => $dueDate];
    }
}
