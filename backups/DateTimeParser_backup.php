<?php

declare(strict_types=1);

namespace App\Service\AI;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Парсер дат и времени для русских голосовых команд
 *
 * Поддерживает:
 * - Относительные даты (сегодня, завтра, послезавтра)
 * - Дни недели (понедельник, вторник, среда, четверг, пятница, суббота, воскресенье)
 * - Конкретные даты (25 ноября, 1 декабря)
 * - Временные диапазоны (с 19:30 до 21:00)
 */
class DateTimeParser
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Парсинг даты из текстового выражения
     *
     * @param string $dateExpression Выражение даты (сегодня, завтра, понедельник, 25 ноября)
     *
     * @return DateTimeImmutable|null
     */
    public function parseDate(string $dateExpression): ?DateTimeImmutable
    {
        $expression = mb_strtolower(trim($dateExpression));

        try {
            // Относительные даты
            return match ($expression) {
                'сегодня', 'today' => new DateTimeImmutable(),
                'завтра', 'tomorrow' => new DateTimeImmutable('+1 day'),
                'послезавтра', 'day_after_tomorrow' => new DateTimeImmutable('+2 days'),
                'через неделю', 'next week' => new DateTimeImmutable('+1 week'),
                'через месяц', 'next month' => new DateTimeImmutable('+1 month'),
                default => $this->parseWeekdayOrConcreteDate($expression)
            };
        } catch (Exception $e) {
            $this->logger->warning('Failed to parse date', [
                'expression' => $dateExpression,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Парсинг даты с установкой конкретного времени (due date - конец дня)
     *
     * @param string $dateExpression Выражение даты
     *
     * @return DateTimeImmutable|null Дата со временем 23:59:59
     */
    public function parseDueDate(string $dateExpression): ?DateTimeImmutable
    {
        $date = $this->parseDate($dateExpression);

        if ($date === null) {
            return null;
        }

        return $date->setTime(23, 59, 59);
    }

    /**
     * Парсинг начальной даты (start date - начало дня)
     *
     * @param string $dateExpression Выражение даты
     *
     * @return DateTimeImmutable|null Дата со временем 00:00:00
     */
    public function parseStartDate(string $dateExpression): ?DateTimeImmutable
    {
        $date = $this->parseDate($dateExpression);

        if ($date === null) {
            return null;
        }

        return $date->setTime(0, 0, 0);
    }

    /**
     * Парсинг даты с установкой конкретного времени
     *
     * @param string $dateExpression Выражение даты (сегодня, завтра)
     * @param string $timeExpression Время (19:30, 14:00)
     *
     * @return DateTimeImmutable|null
     */
    public function parseDateWithTime(string $dateExpression, string $timeExpression): ?DateTimeImmutable
    {
        $date = $this->parseDate($dateExpression);

        if ($date === null) {
            return null;
        }

        // Парсим время из строки "HH:MM"
        if (preg_match('/^(\d{1,2}):(\d{2})$/', trim($timeExpression), $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];

            return $date->setTime($hour, $minute, 0);
        }

        return null;
    }

    /**
     * Парсинг дня недели или конкретной даты
     */
    private function parseWeekdayOrConcreteDate(string $expression): ?DateTimeImmutable
    {
        // Дни недели
        $weekdays = [
            'понедельник' => 'monday',
            'вторник'     => 'tuesday',
            'среда'       => 'wednesday',
            'четверг'     => 'thursday',
            'пятница'     => 'friday',
            'суббота'     => 'saturday',
            'воскресенье' => 'sunday',
            'monday'      => 'monday',
            'tuesday'     => 'tuesday',
            'wednesday'   => 'wednesday',
            'thursday'    => 'thursday',
            'friday'      => 'friday',
            'saturday'    => 'saturday',
            'sunday'      => 'sunday',
        ];

        if (isset($weekdays[$expression])) {
            return $this->getNextWeekday($weekdays[$expression]);
        }

        // Конкретные даты ("25 ноября", "1 декабря")
        return $this->parseConcreteDate($expression);
    }

    /**
     * Получить следующий день недели
     *
     * @param string $weekday Название дня на английском (monday, tuesday, etc.)
     *
     * @return DateTimeImmutable
     */
    private function getNextWeekday(string $weekday): DateTimeImmutable
    {
        $today = new DateTimeImmutable();
        $targetDay = $today->modify('next ' . $weekday);

        // Если сегодня уже этот день недели, берем следующую неделю
        if ($targetDay->format('Y-m-d') === $today->format('Y-m-d')) {
            $targetDay = $today->modify('+1 week');
        }

        return $targetDay;
    }

    /**
     * Парсинг конкретной даты ("25 ноября", "1 декабря")
     *
     * @param string $expression Выражение даты
     *
     * @return DateTimeImmutable|null
     */
    private function parseConcreteDate(string $expression): ?DateTimeImmutable
    {
        $months = [
            'января'   => 1,
            'февраля'  => 2,
            'марта'    => 3,
            'апреля'   => 4,
            'мая'      => 5,
            'июня'     => 6,
            'июля'     => 7,
            'августа'  => 8,
            'сентября' => 9,
            'октября'  => 10,
            'ноября'   => 11,
            'декабря'  => 12,
        ];

        // Ищем паттерн "число месяц" (например "25 ноября")
        foreach ($months as $monthName => $monthNumber) {
            if (str_contains($expression, $monthName)) {
                // Извлекаем число
                if (preg_match('/(\d{1,2})\s*' . preg_quote($monthName, '/') . '/', $expression, $matches)) {
                    $day = (int) $matches[1];
                    $year = (int) date('Y');

                    // Если дата уже прошла в этом году, берем следующий год
                    $date = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $monthNumber, $day));
                    $today = new DateTimeImmutable();

                    if ($date < $today) {
                        $date = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year + 1, $monthNumber, $day));
                    }

                    return $date;
                }
            }
        }

        // Fallback - попробовать стандартный парсинг PHP
        try {
            return new DateTimeImmutable($expression);
        } catch (Exception $e) {
            return null;
        }
    }
}
