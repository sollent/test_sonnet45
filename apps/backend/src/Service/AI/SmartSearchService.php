<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\Database\TaskRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Сервис умного поиска задач по натуральным запросам
 *
 * Интерпретирует натуральные запросы типа "завтра", "на этой неделе", "важные"
 * и конвертирует их в фильтры для репозитория.
 * Следует паттерну Strategy для различных типов поиска
 */
class SmartSearchService
{
    /**
     * Карта приоритетов на русском языке
     */
    private const PRIORITY_MAP = [
        'низкий' => 'low',
        'низкая' => 'low',
        'низкое' => 'low',
        'средний' => 'medium',
        'средняя' => 'medium',
        'среднее' => 'medium',
        'обычный' => 'medium',
        'обычная' => 'medium',
        'обычное' => 'medium',
        'высокий' => 'high',
        'высокая' => 'high',
        'высокое' => 'high',
        'важный' => 'high',
        'важная' => 'high',
        'важное' => 'high',
        'важные' => 'high',
        'срочный' => 'high',
        'срочная' => 'high',
        'срочное' => 'high',
        'срочные' => 'high',
    ];

    /**
     * Карта статусов на русском языке
     */
    private const STATUS_MAP = [
        'новый' => 'new',
        'новая' => 'new',
        'новое' => 'new',
        'новые' => 'new',
        'в работе' => 'in_progress',
        'в процессе' => 'in_progress',
        'выполняется' => 'in_progress',
        'активный' => 'in_progress',
        'активная' => 'in_progress',
        'активное' => 'in_progress',
        'активные' => 'in_progress',
        'готово' => 'done',
        'готовый' => 'done',
        'готовая' => 'done',
        'готовое' => 'done',
        'готовые' => 'done',
        'выполнен' => 'done',
        'выполнена' => 'done',
        'выполнено' => 'done',
        'выполненные' => 'done',
        'завершен' => 'done',
        'завершена' => 'done',
        'завершено' => 'done',
        'завершенные' => 'done',
        'закрыт' => 'done',
        'закрыта' => 'done',
        'закрыто' => 'done',
        'закрытые' => 'done',
    ];

    private TaskRepository $taskRepository;
    private LoggerInterface $logger;

    public function __construct(
        TaskRepository $taskRepository,
        LoggerInterface $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->logger = $logger;
    }

    /**
     * Поиск задач по текстовому запросу
     *
     * @param string $query Поисковый запрос (например, "купить молоко")
     * @param User $user Пользователь
     * @param int $limit Максимальное количество результатов
     * @return Task[] Найденные задачи
     */
    public function searchTasks(string $query, User $user, int $limit = 10): array
    {
        $this->logger->info('Smart search for tasks', [
            'query' => $query,
            'user_id' => $user->getId()
        ]);

        // Сначала пытаемся точный поиск по названию
        $tasks = $this->taskRepository->searchByTitle($query, $user, $limit);

        // Если не нашли, пробуем частичный поиск
        if (empty($tasks)) {
            // Разбиваем запрос на слова для поиска по ключевым словам
            $keywords = $this->extractKeywords($query);

            foreach ($keywords as $keyword) {
                $tasks = $this->taskRepository->searchByTitle($keyword, $user, $limit);
                if (!empty($tasks)) {
                    break;
                }
            }
        }

        $this->logger->info('Smart search results', [
            'query' => $query,
            'found' => count($tasks)
        ]);

        return $tasks;
    }

    /**
     * Поиск задач с применением фильтров
     *
     * @param array $filters Фильтры из ParsedCommand
     * @param User $user Пользователь
     * @return Task[] Отфильтрованные задачи
     */
    public function filterTasks(array $filters, User $user): array
    {
        $this->logger->info('Filtering tasks', [
            'filters' => $filters,
            'user_id' => $user->getId()
        ]);

        $criteria = $this->buildCriteria($filters, $user);

        // Используем репозиторий для поиска с критериями
        $tasks = $this->taskRepository->findByFilters($criteria);

        $this->logger->info('Filter results', [
            'filters' => $filters,
            'found' => count($tasks)
        ]);

        return $tasks;
    }

    /**
     * Поиск одной задачи по лучшему совпадению
     *
     * @param string $search Поисковый запрос
     * @param User $user Пользователь
     * @return Task|null Найденная задача или null
     */
    public function findBestMatch(string $search, User $user): ?Task
    {
        $tasks = $this->searchTasks($search, $user, 1);

        return !empty($tasks) ? $tasks[0] : null;
    }

    /**
     * Построение критериев фильтрации из натурального языка
     */
    private function buildCriteria(array $filters, User $user): array
    {
        $criteria = ['user' => $user];

        // Обработка даты
        if (isset($filters['date'])) {
            $dateRange = $this->parseDateExpression($filters['date']);
            if ($dateRange) {
                $criteria['dueDate'] = $dateRange;
            }
        }

        // Обработка приоритета
        if (isset($filters['priority'])) {
            $priority = $this->parsePriority($filters['priority']);
            if ($priority) {
                $criteria['priority'] = $priority;
            }
        }

        // Обработка статуса
        if (isset($filters['status'])) {
            $status = $this->parseStatus($filters['status']);
            if ($status) {
                $criteria['status'] = $status;
            }
        }

        // Обработка тегов
        if (isset($filters['tags'])) {
            $criteria['tags'] = is_array($filters['tags'])
                ? $filters['tags']
                : [$filters['tags']];
        }

        // Обработка поиска по тексту
        if (isset($filters['search'])) {
            $criteria['search'] = $filters['search'];
        }

        return $criteria;
    }

    /**
     * Парсинг выражений даты (сегодня, завтра, на этой неделе и т.д.)
     *
     * @return array|null ['from' => DateTimeImmutable, 'to' => DateTimeImmutable]
     */
    private function parseDateExpression(string $expression): ?array
    {
        $now = new DateTimeImmutable();
        $expression = mb_strtolower(trim($expression));

        switch ($expression) {
            case 'сегодня':
            case 'today':
                return [
                    'from' => $now->setTime(0, 0, 0),
                    'to' => $now->setTime(23, 59, 59)
                ];

            case 'завтра':
            case 'tomorrow':
                $tomorrow = $now->modify('+1 day');
                return [
                    'from' => $tomorrow->setTime(0, 0, 0),
                    'to' => $tomorrow->setTime(23, 59, 59)
                ];

            case 'вчера':
            case 'yesterday':
                $yesterday = $now->modify('-1 day');
                return [
                    'from' => $yesterday->setTime(0, 0, 0),
                    'to' => $yesterday->setTime(23, 59, 59)
                ];

            case 'на этой неделе':
            case 'эта неделя':
            case 'this week':
                $monday = $now->modify('monday this week');
                $sunday = $now->modify('sunday this week');
                return [
                    'from' => $monday->setTime(0, 0, 0),
                    'to' => $sunday->setTime(23, 59, 59)
                ];

            case 'на следующей неделе':
            case 'следующая неделя':
            case 'next week':
                $monday = $now->modify('monday next week');
                $sunday = $now->modify('sunday next week');
                return [
                    'from' => $monday->setTime(0, 0, 0),
                    'to' => $sunday->setTime(23, 59, 59)
                ];

            case 'в этом месяце':
            case 'этот месяц':
            case 'this month':
                $firstDay = $now->modify('first day of this month');
                $lastDay = $now->modify('last day of this month');
                return [
                    'from' => $firstDay->setTime(0, 0, 0),
                    'to' => $lastDay->setTime(23, 59, 59)
                ];

            case 'в следующем месяце':
            case 'следующий месяц':
            case 'next month':
                $firstDay = $now->modify('first day of next month');
                $lastDay = $now->modify('last day of next month');
                return [
                    'from' => $firstDay->setTime(0, 0, 0),
                    'to' => $lastDay->setTime(23, 59, 59)
                ];

            case 'просроченные':
            case 'overdue':
                return [
                    'from' => null,
                    'to' => $now->modify('-1 second')
                ];

            default:
                // Попытка распарсить конкретную дату
                try {
                    $date = new DateTimeImmutable($expression);
                    return [
                        'from' => $date->setTime(0, 0, 0),
                        'to' => $date->setTime(23, 59, 59)
                    ];
                } catch (\Exception $e) {
                    $this->logger->warning('Could not parse date expression', [
                        'expression' => $expression
                    ]);
                    return null;
                }
        }
    }

    /**
     * Парсинг приоритета из натурального языка
     */
    private function parsePriority(string $priority): ?string
    {
        $priority = mb_strtolower(trim($priority));

        // Проверяем по карте соответствий
        if (isset(self::PRIORITY_MAP[$priority])) {
            return self::PRIORITY_MAP[$priority];
        }

        // Проверяем английские варианты
        if (in_array($priority, ['low', 'medium', 'high'], true)) {
            return $priority;
        }

        return null;
    }

    /**
     * Парсинг статуса из натурального языка
     */
    private function parseStatus(string $status): ?string
    {
        $status = mb_strtolower(trim($status));

        // Проверяем по карте соответствий
        if (isset(self::STATUS_MAP[$status])) {
            return self::STATUS_MAP[$status];
        }

        // Проверяем английские варианты
        if (in_array($status, ['new', 'in_progress', 'done'], true)) {
            return $status;
        }

        return null;
    }

    /**
     * Извлечение ключевых слов из запроса
     */
    private function extractKeywords(string $query): array
    {
        // Убираем стоп-слова и разбиваем на слова
        $stopWords = ['и', 'в', 'на', 'с', 'к', 'у', 'за', 'от', 'до', 'для', 'или', 'но'];

        $words = preg_split('/\s+/u', mb_strtolower($query));

        $keywords = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords, true);
        });

        return array_values($keywords);
    }
}