<?php

declare(strict_types=1);

namespace App\Service\AI\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Service\AI\SmartSearchService;
use RuntimeException;

/**
 * Сервис для поиска задач
 *
 * Централизует логику поиска задач, устраняя дублирование кода.
 * Следует принципу Single Responsibility.
 */
class TaskFinder
{
    private SmartSearchService $searchService;

    public function __construct(SmartSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Найти задачу или выбросить исключение
     *
     * @param string $search Поисковый запрос
     * @param User $user Пользователь
     * @return Task Найденная задача
     * @throws RuntimeException Если задача не найдена
     */
    public function findOrFail(string $search, User $user): Task
    {
        $task = $this->find($search, $user);

        if (!$task) {
            throw new RuntimeException(sprintf('Задача "%s" не найдена', $search));
        }

        return $task;
    }

    /**
     * Найти задачу или вернуть null
     *
     * @param string $search Поисковый запрос
     * @param User $user Пользователь
     * @return Task|null Найденная задача или null
     */
    public function find(string $search, User $user): ?Task
    {
        return $this->searchService->findBestMatch($search, $user);
    }

    /**
     * Найти несколько задач по списку названий
     *
     * @param array<string> $searches Список поисковых запросов
     * @param User $user Пользователь
     * @return array{found: array<Task>, not_found: array<string>}
     */
    public function findMultiple(array $searches, User $user): array
    {
        $found = [];
        $notFound = [];

        foreach ($searches as $search) {
            $task = $this->find($search, $user);
            if ($task) {
                $found[] = $task;
            } else {
                $notFound[] = $search;
            }
        }

        return [
            'found' => $found,
            'not_found' => $notFound,
        ];
    }

    /**
     * Найти родительскую задачу из параметров
     *
     * @param array $parameters Параметры с parent_search/parent/parent_task
     * @param User $user Пользователь
     * @return Task|null Найденная родительская задача
     */
    public function findParent(array $parameters, User $user): ?Task
    {
        $parentSearch = $this->extractParentSearch($parameters);

        if (empty($parentSearch)) {
            return null;
        }

        return $this->find($parentSearch, $user);
    }

    /**
     * Найти родительскую задачу или выбросить исключение
     *
     * @param array $parameters Параметры с parent_search/parent/parent_task
     * @param User $user Пользователь
     * @return Task Найденная родительская задача
     * @throws RuntimeException Если задача не найдена или не указана
     */
    public function findParentOrFail(array $parameters, User $user): Task
    {
        $parentSearch = $this->extractParentSearch($parameters);

        if (empty($parentSearch)) {
            throw new RuntimeException('Родительская задача не указана');
        }

        $parentTask = $this->find($parentSearch, $user);

        if (!$parentTask) {
            throw new RuntimeException(sprintf('Родительская задача "%s" не найдена', $parentSearch));
        }

        return $parentTask;
    }

    /**
     * Фильтровать задачи по критериям
     *
     * @param array $filters Фильтры (date, priority, status, search)
     * @param User $user Пользователь
     * @return array<Task> Отфильтрованные задачи
     */
    public function filter(array $filters, User $user): array
    {
        return $this->searchService->filterTasks($filters, $user);
    }

    /**
     * Извлечь поисковый параметр из различных вариантов
     *
     * @param array $parameters Параметры
     * @return string|null Поисковый запрос
     */
    public function extractSearch(array $parameters): ?string
    {
        return $parameters['search']
            ?? $parameters['title']
            ?? $parameters['name']
            ?? null;
    }

    /**
     * Извлечь поисковый параметр для родительской задачи
     *
     * @param array $parameters Параметры
     * @return string|null Поисковый запрос для родителя
     */
    public function extractParentSearch(array $parameters): ?string
    {
        return $parameters['parent_search']
            ?? $parameters['parent']
            ?? $parameters['parent_task']
            ?? null;
    }
}