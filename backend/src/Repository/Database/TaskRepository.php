<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Dto\Request\Task\TaskFilterDto;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Find all tasks for a user, optionally filtered
     *
     * @return Task[]
     */
    public function findUserTasks(
        User $user,
        ?TaskStatus $status = null,
        ?bool $includeArchived = false,
        ?bool $onlyParentTasks = true
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($onlyParentTasks) {
            $qb->andWhere('t.parentTask IS NULL');
        }

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if (!$includeArchived) {
            $qb->andWhere('t.isArchived = :archived')
                ->setParameter('archived', false);
        }

        $qb->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find today's tasks for a user
     *
     * @return Task[]
     */
    public function findTodayTasks(User $user, ?TaskFilterDto $filters = null): array
    {
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = new \DateTimeImmutable('today 23:59:59');

        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere(
                '(t.dueDate BETWEEN :todayStart AND :todayEnd) OR (t.startDate BETWEEN :todayStart AND :todayEnd)'
            )
            ->setParameter('user', $user)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd);

        // Apply default status filter only if no status or completed filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        $completed = $filters ? $filters->getCompleted() : null;
        if ((!$statuses || empty($statuses)) && $completed === null) {
            $qb->andWhere('t.status != :completedStatus')
               ->setParameter('completedStatus', TaskStatus::COMPLETED);
        }

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.priority', 'DESC')
           ->addOrderBy('t.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find overdue tasks for a user
     *
     * @return Task[]
     */
    public function findOverdueTasks(User $user): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :completed')
            ->andWhere('t.dueDate < :now')
            ->setParameter('user', $user)
            ->setParameter('completed', TaskStatus::COMPLETED)
            ->setParameter('now', $now)
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming tasks for a user (next 7 days)
     *
     * @return Task[]
     */
    public function findUpcomingTasks(User $user, int $days = 7, ?TaskFilterDto $filters = null): array
    {
        $tomorrow = new \DateTimeImmutable('tomorrow');
        $endDate = new \DateTimeImmutable("+{$days} days");

        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('(t.startDate >= :tomorrow AND t.startDate <= :endDate) OR (t.dueDate >= :tomorrow AND t.dueDate <= :endDate)')
            ->setParameter('user', $user)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('endDate', $endDate);

        // Apply default status filter only if no status or completed filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        $completed = $filters ? $filters->getCompleted() : null;
        if ((!$statuses || empty($statuses)) && $completed === null) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', TaskStatus::COMPLETED);
        }

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.dueDate', 'ASC')
           ->addOrderBy('t.priority', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findActiveTasks(User $user, ?TaskFilterDto $filters = null): array
    {
        $todayStart = new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('(
                (t.dueDate IS NOT NULL AND t.dueDate >= :todayStart) OR
                (t.startDate IS NOT NULL AND t.startDate >= :todayStart)
            )')
            ->setParameter('user', $user)
            ->setParameter('todayStart', $todayStart);

        // Apply default status filter only if no status or completed filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        $completed = $filters ? $filters->getCompleted() : null;
        if ((!$statuses || empty($statuses)) && $completed === null) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', TaskStatus::COMPLETED);
        }

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.dueDate', 'ASC')
           ->addOrderBy('t.priority', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find tasks by tag for a user
     *
     * @return Task[]
     */
    public function findTasksByTag(User $user, int $tagId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.tags', 'tag')
            ->where('t.user = :user')
            ->andWhere('tag.id = :tagId')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->setParameter('user', $user)
            ->setParameter('tagId', $tagId)
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search tasks by title or description
     *
     * @return Task[]
     */
    public function searchTasks(User $user, string $query, ?TaskFilterDto $filters = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('(LOWER(t.title) LIKE :query OR LOWER(t.description) LIKE :query)')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . strtolower($query) . '%');

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.sortOrder', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get task statistics for a user
     */
    public function getUserTaskStatistics(User $user): array
    {
        // OPTIMIZATION: Single query with conditional aggregation for all stats including overdue
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN t.status = :pending THEN 1 END) as pending_count,
                COUNT(CASE WHEN t.status = :in_progress THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN t.status = :completed THEN 1 END) as completed_count,
                COUNT(CASE WHEN t.status = :cancelled THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN t.status != :completed AND t.due_date < :now THEN 1 END) as overdue_count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.is_archived = false',
            [
                'user_id' => $user->getId(),
                'pending' => TaskStatus::PENDING->value,
                'in_progress' => TaskStatus::IN_PROGRESS->value,
                'completed' => TaskStatus::COMPLETED->value,
                'cancelled' => TaskStatus::CANCELLED->value,
                'now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]
        )->fetchAssociative();

        return [
            'total' => (int)$data['total'],
            TaskStatus::PENDING->value => (int)$data['pending_count'],
            TaskStatus::IN_PROGRESS->value => (int)$data['in_progress_count'],
            TaskStatus::COMPLETED->value => (int)$data['completed_count'],
            TaskStatus::CANCELLED->value => (int)$data['cancelled_count'],
            'overdue' => (int)$data['overdue_count'],
        ];
    }

    /**
     * Find task with all nested subtasks loaded
     */
    public function findWithSubtasks(int $id): ?Task
    {
        $task = $this->createQueryBuilder('t')
            ->leftJoin('t.subtasks', 's')
            ->leftJoin('t.tags', 'tag')
            ->addSelect('s')
            ->addSelect('tag')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
            
        // If task has subtasks, load their subtasks recursively
        if ($task && $task->getSubtasks()->count() > 0) {
            foreach ($task->getSubtasks() as $subtask) {
                $this->loadSubtasksRecursively($subtask);
            }
        }
        
        return $task;
    }
    
    /**
     * Recursively load subtasks
     */
    private function loadSubtasksRecursively(Task $task): void
    {
        $subtasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->addSelect('tag')
            ->where('t.parentTask = :parent')
            ->setParameter('parent', $task)
            ->getQuery()
            ->getResult();
            
        foreach ($subtasks as $subtask) {
            if ($subtask->getSubtasks()->count() > 0) {
                $this->loadSubtasksRecursively($subtask);
            }
        }
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find tasks for a specific date range
     */
    public function findTasksByDateRange(User $user, \DateTime $startDate, \DateTime $endDate, bool $includeCompleted = true): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->addSelect('tag')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL') // Only root tasks
            ->andWhere('(
                (t.startDate BETWEEN :startDate AND :endDate) OR
                (t.dueDate BETWEEN :startDate AND :endDate) OR
                (t.startDate <= :startDate AND t.dueDate >= :endDate) OR
                (t.startDate IS NULL AND t.dueDate BETWEEN :startDate AND :endDate) OR
                (t.dueDate IS NULL AND t.startDate BETWEEN :startDate AND :endDate)
            )')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if (!$includeCompleted) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', TaskStatus::COMPLETED);
        }

        return $qb->orderBy('t.startDate', 'ASC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tasks for a specific day
     */
    public function findTasksByDay(User $user, \DateTime $date, bool $includeCompleted = true): array
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);

        return $this->findTasksByDateRange($user, $startOfDay, $endOfDay, $includeCompleted);
    }

    /**
     * Find tasks for calendar month view
     */
    public function findTasksForMonth(User $user, int $year, int $month, bool $includeCompleted = true): array
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->findTasksByDateRange($user, $startDate, $endDate, $includeCompleted);
    }

    /**
     * Find tasks for calendar week view
     */
    public function findTasksForWeek(User $user, \DateTime $weekStart, bool $includeCompleted = true): array
    {
        $startDate = clone $weekStart;
        $startDate->setTime(0, 0, 0);
        
        $endDate = clone $weekStart;
        $endDate->modify('+6 days')->setTime(23, 59, 59);

        return $this->findTasksByDateRange($user, $startDate, $endDate, $includeCompleted);
    }

    public function findOverdueByUserPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.dueDate < :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTimeImmutable());

        // Apply default status filter only if no status or completed filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        $completed = $filters ? $filters->getCompleted() : null;
        if ((!$statuses || empty($statuses)) && $completed === null) {
            $qb->andWhere('t.status != :completedStatus')
               ->setParameter('completedStatus', TaskStatus::COMPLETED);
        }

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.dueDate', 'ASC')
           ->addOrderBy('t.priority', 'DESC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }

    public function findUnscheduledByUserPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.dueDate IS NULL')
            ->setParameter('user', $user);

        // Apply default status filter only if no status or completed filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        $completed = $filters ? $filters->getCompleted() : null;
        if ((!$statuses || empty($statuses)) && $completed === null) {
            $qb->andWhere('t.status != :completedStatus')
               ->setParameter('completedStatus', TaskStatus::COMPLETED);
        }

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        $qb->orderBy('t.createdAt', 'DESC')
           ->addOrderBy('t.priority', 'DESC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }

    /**
     * Apply filters to query builder
     *
     * @param QueryBuilder $qb
     * @param TaskFilterDto $filters
     */
    private function applyFilters(QueryBuilder $qb, TaskFilterDto $filters): void
    {
        if (!$filters->hasFilters()) {
            return;
        }

        // Filter by tags
        $tags = $filters->getTags();
        if ($tags !== null && !empty($tags)) {
            $qb->join('t.tags', 'filter_tag')
               ->andWhere('filter_tag.id IN (:filterTags)')
               ->setParameter('filterTags', $tags);
        }

        // Filter by completion status
        $completed = $filters->getCompleted();
        if ($completed !== null) {
            if ($completed) {
                $qb->andWhere('t.status = :completedFilterStatus')
                   ->setParameter('completedFilterStatus', TaskStatus::COMPLETED);
            } else {
                $qb->andWhere('t.status != :notCompletedFilterStatus')
                   ->setParameter('notCompletedFilterStatus', TaskStatus::COMPLETED);
            }
        }

        // Filter by date range
        $dateFrom = $filters->getDateFrom();
        if ($dateFrom !== null) {
            $dateFromObj = new \DateTimeImmutable($dateFrom);
            $qb->andWhere('(t.dueDate >= :filterDateFrom OR t.startDate >= :filterDateFrom)')
               ->setParameter('filterDateFrom', $dateFromObj);
        }

        $dateTo = $filters->getDateTo();
        if ($dateTo !== null) {
            $dateToObj = new \DateTimeImmutable($dateTo . ' 23:59:59');
            $qb->andWhere('(t.dueDate <= :filterDateTo OR t.startDate <= :filterDateTo)')
               ->setParameter('filterDateTo', $dateToObj);
        }

        // Filter by priorities
        $priorities = $filters->getPriorities();
        if ($priorities !== null && !empty($priorities)) {
            $priorityEnums = array_map(fn($p) => TaskPriority::from($p), $priorities);
            $qb->andWhere('t.priority IN (:filterPriorities)')
               ->setParameter('filterPriorities', $priorityEnums);
        }

        // Filter by statuses
        $statuses = $filters->getStatuses();
        if ($statuses !== null && !empty($statuses)) {
            $statusEnums = array_map(fn($s) => TaskStatus::from($s), $statuses);
            $qb->andWhere('t.status IN (:filterStatuses)')
               ->setParameter('filterStatuses', $statusEnums);
        }
    }

    // ==================== ANALYTICS METHODS ====================

    /**
     * Find tasks created between dates
     */
    public function findTasksCreatedBetween(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.createdAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tasks completed between dates
     */
    public function findTasksCompletedBetween(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average completion time in days - OPTIMIZED VERSION
     * Uses direct SQL calculation instead of loading entities
     */
    public function getAverageCompletionTime(User $user): float
    {
        // OPTIMIZATION: Direct SQL calculation
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT AVG(EXTRACT(EPOCH FROM (t.completed_at - t.created_at)) / 86400) as avg_days
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at IS NOT NULL
               AND t.created_at IS NOT NULL',
            [
                'user_id' => $user->getId()
            ]
        )->fetchAssociative();

        return round((float)($data['avg_days'] ?? 0), 1);
    }

    /**
     * Get on-time completion rate (percentage) - OPTIMIZED VERSION
     * Uses single query with conditional aggregation
     */
    public function getOnTimeCompletionRate(User $user): int
    {
        // OPTIMIZATION: Single query with conditional aggregation
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN t.completed_at <= t.due_date THEN 1 END) as on_time
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.due_date IS NOT NULL
               AND t.completed_at IS NOT NULL',
            [
                'user_id' => $user->getId()
            ]
        )->fetchAssociative();

        $total = (int)$data['total'];
        if ($total == 0) {
            return 100;
        }

        $onTime = (int)$data['on_time'];
        return (int)round(($onTime / $total) * 100);
    }

    /**
     * Get most productive day of week - OPTIMIZED VERSION
     * Uses direct SQL with day extraction and ordering
     */
    public function getMostProductiveDay(User $user): ?string
    {
        // OPTIMIZATION: Direct SQL query with day extraction and ordering
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                CASE EXTRACT(DOW FROM t.completed_at)
                    WHEN 0 THEN \'Sunday\'
                    WHEN 1 THEN \'Monday\'
                    WHEN 2 THEN \'Tuesday\'
                    WHEN 3 THEN \'Wednesday\'
                    WHEN 4 THEN \'Thursday\'
                    WHEN 5 THEN \'Friday\'
                    WHEN 6 THEN \'Saturday\'
                END as day_name,
                COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at IS NOT NULL
             GROUP BY EXTRACT(DOW FROM t.completed_at)
             ORDER BY count DESC, EXTRACT(DOW FROM t.completed_at) ASC
             LIMIT 1',
            [
                'user_id' => $user->getId()
            ]
        )->fetchAssociative();

        return $data ? $data['day_name'] : null;
    }

    /**
     * Get completion timeline data for chart - OPTIMIZED VERSION
     * Uses single queries with date grouping instead of multiple queries per day
     */
    public function getCompletionTimelineData(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $dates = [];
        $created = [];
        $completed = [];
        $overdue = [];

        $current = \DateTimeImmutable::createFromInterface($start);
        $endDate = \DateTimeImmutable::createFromInterface($end);

        // OPTIMIZATION: Generate all dates first
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current = $current->modify('+1 day');
        }

        $startDate = $start->format('Y-m-d 00:00:00');
        $endDateStr = $end->format('Y-m-d 23:59:59');

        // OPTIMIZATION: Single query for created tasks with date grouping
        $createdData = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(t.created_at) as date, COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.created_at BETWEEN :start_date AND :end_date
             GROUP BY DATE(t.created_at)
             ORDER BY DATE(t.created_at)',
            [
                'user_id' => $user->getId(),
                'start_date' => $startDate,
                'end_date' => $endDateStr
            ]
        )->fetchAllAssociative();

        // OPTIMIZATION: Single query for completed tasks with date grouping
        $completedData = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(t.completed_at) as date, COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at BETWEEN :start_date AND :end_date
             GROUP BY DATE(t.completed_at)
             ORDER BY DATE(t.completed_at)',
            [
                'user_id' => $user->getId(),
                'start_date' => $startDate,
                'end_date' => $endDateStr
            ]
        )->fetchAllAssociative();

        // OPTIMIZATION: Single query for overdue tasks calculation per day
        $overdueData = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                DATE(t.due_date) as due_date,
                COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.status != :completed_status
               AND t.due_date BETWEEN :start_date AND :end_date
             GROUP BY DATE(t.due_date)
             ORDER BY DATE(t.due_date)',
            [
                'user_id' => $user->getId(),
                'completed_status' => TaskStatus::COMPLETED->value,
                'start_date' => $startDate,
                'end_date' => $endDateStr
            ]
        )->fetchAllAssociative();

        // OPTIMIZATION: Convert results to arrays indexed by date
        $createdMap = array_column($createdData, 'count', 'date');
        $completedMap = array_column($completedData, 'count', 'date');
        $overdueMap = array_column($overdueData, 'count', 'due_date');

        // OPTIMIZATION: Fill arrays with data or zeros
        foreach ($dates as $date) {
            $created[] = (int)($createdMap[$date] ?? 0);
            $completed[] = (int)($completedMap[$date] ?? 0);
            $overdue[] = (int)($overdueMap[$date] ?? 0);
        }

        return [
            'dates' => $dates,
            'created' => $created,
            'completed' => $completed,
            'overdue' => $overdue
        ];
    }

    /**
     * Get priority breakdown with completion stats - OPTIMIZED VERSION
     * Uses single query with conditional aggregation instead of multiple queries
     */
    public function getPriorityBreakdown(User $user): array
    {
        // OPTIMIZATION: Single query with conditional aggregation
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                LOWER(t.priority) as priority,
                COUNT(*) as total,
                COUNT(CASE WHEN t.status = :completed THEN 1 END) as completed,
                COUNT(CASE WHEN t.status = :in_progress THEN 1 END) as in_progress
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.is_archived = false
             GROUP BY t.priority',
            [
                'user_id' => $user->getId(),
                'completed' => TaskStatus::COMPLETED->value,
                'in_progress' => TaskStatus::IN_PROGRESS->value
            ]
        )->fetchAllAssociative();

        $result = [];
        foreach ($data as $row) {
            $result[$row['priority']] = [
                'total' => (int)$row['total'],
                'completed' => (int)$row['completed'],
                'inProgress' => (int)$row['in_progress'],
                'pending' => (int)$row['total'] - (int)$row['completed'] - (int)$row['in_progress']
            ];
        }

        // OPTIMIZATION: Ensure all priority types are present (even with zero counts)
        foreach (\App\Enum\TaskPriority::cases() as $priority) {
            $key = strtolower($priority->value);
            if (!isset($result[$key])) {
                $result[$key] = [
                    'total' => 0,
                    'completed' => 0,
                    'inProgress' => 0,
                    'pending' => 0
                ];
            }
        }

        return $result;
    }

    /**
     * Get productivity heatmap (GitHub-style) - OPTIMIZED VERSION
     * Uses direct SQL query with grouping instead of loading all entities
     */
    public function getProductivityHeatmap(User $user, int $year): array
    {
        // OPTIMIZATION: Direct SQL with date grouping
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DATE(t.completed_at) as date, COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND EXTRACT(YEAR FROM t.completed_at) = :year
             GROUP BY DATE(t.completed_at)
             ORDER BY DATE(t.completed_at)',
            [
                'user_id' => $user->getId(),
                'year' => $year
            ]
        )->fetchAllAssociative();

        // OPTIMIZATION: Convert to associative array
        return array_column($data, 'count', 'date');
    }

    /**
     * Get weekday productivity (Monday-Sunday) - OPTIMIZED VERSION
     * Uses direct SQL with EXTRACT(DOW) for weekday calculation
     */
    public function getWeekdayProductivity(User $user): array
    {
        // OPTIMIZATION: Direct SQL query with weekday extraction
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                CASE EXTRACT(DOW FROM t.completed_at)
                    WHEN 0 THEN \'Sunday\'
                    WHEN 1 THEN \'Monday\'
                    WHEN 2 THEN \'Tuesday\'
                    WHEN 3 THEN \'Wednesday\'
                    WHEN 4 THEN \'Thursday\'
                    WHEN 5 THEN \'Friday\'
                    WHEN 6 THEN \'Saturday\'
                END as day_name,
                COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at IS NOT NULL
             GROUP BY EXTRACT(DOW FROM t.completed_at)
             ORDER BY EXTRACT(DOW FROM t.completed_at)',
            [
                'user_id' => $user->getId()
            ]
        )->fetchAllAssociative();

        // OPTIMIZATION: Initialize all days with zero
        $days = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];

        // OPTIMIZATION: Fill with actual data
        foreach ($data as $row) {
            $days[$row['day_name']] = (int)$row['count'];
        }

        return $days;
    }

    /**
     * Get most productive hour of day - OPTIMIZED VERSION
     * Uses direct SQL with hour extraction and ordering
     */
    public function getMostProductiveHour(User $user): ?int
    {
        // OPTIMIZATION: Direct SQL query with hour extraction and ordering
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT EXTRACT(HOUR FROM t.completed_at) as hour, COUNT(*) as count
             FROM task t
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND t.completed_at IS NOT NULL
             GROUP BY EXTRACT(HOUR FROM t.completed_at)
             ORDER BY count DESC, EXTRACT(HOUR FROM t.completed_at) ASC
             LIMIT 1',
            [
                'user_id' => $user->getId()
            ]
        )->fetchAssociative();

        return $data ? (int)$data['hour'] : null;
    }

    /**
     * Get tag completion statistics - OPTIMIZED VERSION
     * Uses single query with conditional aggregation
     */
    public function getTagCompletionStats(User $user, int $tagId): array
    {
        // OPTIMIZATION: Single query with conditional aggregation
        $data = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN t.status = :completed THEN 1 END) as completed
             FROM task t
             INNER JOIN task_tags tt ON t.id = tt.task_id
             WHERE t.user_id = :user_id
               AND t.parent_task_id IS NULL
               AND tt.tag_id = :tag_id',
            [
                'user_id' => $user->getId(),
                'tag_id' => $tagId,
                'completed' => TaskStatus::COMPLETED->value
            ]
        )->fetchAssociative();

        $total = (int)($data['total'] ?? 0);
        $completed = (int)($data['completed'] ?? 0);
        $completionRate = $total > 0 ? (int)round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'completionRate' => $completionRate
        ];
    }
}
