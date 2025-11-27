<?php

declare(strict_types=1);

namespace App\Service\AI;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * ОПТИМИЗИРОВАННЫЙ Парсер дат и времени для русских голосовых команд
 *
 * Версия 2.0 - Синхронизирован с LLM форматами
 * Поддерживает стандартизированные форматы из LLM SYSTEM_PROMPT:
 * - Относительные: today, tomorrow, day_after_tomorrow
 * - Периоды: next_week, next_month, this_week
 * - Дни недели: monday-sunday
 * - Конкретные даты: YYYY-MM-DD или "25 ноября"
 * - Время: HH:MM (24-часовой формат)
 */
class DateTimeParser
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * ОПТИМИЗИРОВАННЫЙ Парсинг даты из текстового выражения
     * Поддерживает стандартизированные форматы из LLM
     *
     * @param string $dateExpression Стандартизированное выражение даты из LLM
     *
     * @return DateTimeImmutable|null
     */
    public function parseDate(string $dateExpression): ?DateTimeImmutable
    {
        $expression = mb_strtolower(trim($dateExpression));

        try {
            // Стандартизированные относительные даты (из LLM)
            return match ($expression) {
                // Относительные даты
                'today', 'сегодня'                             => new DateTimeImmutable(),
                'tomorrow', 'завтра'                            => new DateTimeImmutable('+1 day'),
                'day_after_tomorrow', 'послезавтра'            => new DateTimeImmutable('+2 days'),
                'yesterday', 'вчера'                           => new DateTimeImmutable('-1 day'),

                // Периоды
                'next_week', 'через неделю'                    => new DateTimeImmutable('+1 week'),
                'next_month', 'через месяц'                    => new DateTimeImmutable('+1 month'),
                'this_week', 'эта неделя'                      => $this->getStartOfWeek(),

                // Дни недели (английские - стандарт из LLM)
                'monday'                                        => $this->getNextWeekday('monday'),
                'tuesday'                                       => $this->getNextWeekday('tuesday'),
                'wednesday'                                     => $this->getNextWeekday('wednesday'),
                'thursday'                                      => $this->getNextWeekday('thursday'),
                'friday'                                        => $this->getNextWeekday('friday'),
                'saturday'                                      => $this->getNextWeekday('saturday'),
                'sunday'                                        => $this->getNextWeekday('sunday'),

                // Русские дни недели (на случай если придут от пользователя)
                'понедельник'                                   => $this->getNextWeekday('monday'),
                'вторник'                                       => $this->getNextWeekday('tuesday'),
                'среда'                                         => $this->getNextWeekday('wednesday'),
                'четверг'                                       => $this->getNextWeekday('thursday'),
                'пятница'                                       => $this->getNextWeekday('friday'),
                'суббота'                                       => $this->getNextWeekday('saturday'),
                'воскресенье'                                   => $this->getNextWeekday('sunday'),

                // Пытаемся парсить другие форматы
                default                                         => $this->parseSpecialDateFormat($expression)
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
     * Парсинг специальных форматов дат
     * Поддерживает YYYY-MM-DD и русские даты "25 ноября"
     */
    private function parseSpecialDateFormat(string $expression): ?DateTimeImmutable
    {
        // Проверка формата YYYY-MM-DD (стандарт из LLM для конкретных дат)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expression)) {
            try {
                return new DateTimeImmutable($expression);
            } catch (Exception $e) {
                $this->logger->warning('Invalid date format YYYY-MM-DD', [
                    'expression' => $expression,
                    'error'      => $e->getMessage(),
                ]);
                return null;
            }
        }

        // Парсинг конкретных дат на русском ("25 ноября")
        return $this->parseConcreteRussianDate($expression);
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
     * ОПТИМИЗИРОВАННЫЙ Парсинг даты с установкой конкретного времени
     * Время ВСЕГДА приходит из LLM в формате HH:MM
     *
     * @param string $dateExpression Выражение даты (today, tomorrow, monday, etc.)
     * @param string $timeExpression Время в формате HH:MM
     *
     * @return DateTimeImmutable|null
     */
    public function parseDateWithTime(string $dateExpression, string $timeExpression): ?DateTimeImmutable
    {
        $date = $this->parseDate($dateExpression);

        if ($date === null) {
            return null;
        }

        // Парсим время из стандартизированного формата HH:MM
        if (preg_match('/^(\d{1,2}):(\d{2})$/', trim($timeExpression), $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];

            // Валидация времени
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return $date->setTime($hour, $minute, 0);
            }
        }

        $this->logger->warning('Invalid time format', [
            'time' => $timeExpression,
            'expected_format' => 'HH:MM',
        ]);

        return null;
    }

    /**
     * Получить начало текущей недели (понедельник)
     *
     * @return DateTimeImmutable
     */
    private function getStartOfWeek(): DateTimeImmutable
    {
        $today = new DateTimeImmutable();
        $dayOfWeek = (int) $today->format('N'); // 1 = Monday, 7 = Sunday

        if ($dayOfWeek === 1) {
            return $today->setTime(0, 0, 0);
        }

        return $today->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
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
        $todayWeekday = strtolower($today->format('l')); // Полное название дня недели

        // Если сегодня этот день, берем следующую неделю
        if ($todayWeekday === $weekday) {
            return $today->modify('+1 week');
        }

        // Иначе берем ближайший
        return $today->modify('next ' . $weekday);
    }

    /**
     * Парсинг конкретной даты на русском ("25 ноября", "1 декабря")
     *
     * @param string $expression Выражение даты
     *
     * @return DateTimeImmutable|null
     */
    private function parseConcreteRussianDate(string $expression): ?DateTimeImmutable
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

                    try {
                        // Если дата уже прошла в этом году, берем следующий год
                        $date = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $monthNumber, $day));
                        $today = new DateTimeImmutable('today');

                        if ($date < $today) {
                            $date = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year + 1, $monthNumber, $day));
                        }

                        return $date;
                    } catch (Exception $e) {
                        $this->logger->warning('Failed to create date from Russian format', [
                            'expression' => $expression,
                            'error'      => $e->getMessage(),
                        ]);
                        return null;
                    }
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