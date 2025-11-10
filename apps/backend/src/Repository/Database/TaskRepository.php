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
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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
    public function findTodayTasks(User $user, ?TaskFilterDto $filters = null, bool $onlyWithSubtasks = false): array
    {
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = new \DateTimeImmutable('today 23:59:59');

        // OPTIMIZED: Two-step approach to avoid slow ROW_NUMBER() OVER() queries
        // Step 1: Get task IDs with simple query (fast, no joins with subtasks)
        $idsQb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere(
                '(t.dueDate BETWEEN :todayStart AND :todayEnd) OR (t.startDate BETWEEN :todayStart AND :todayEnd)'
            )
            ->setParameter('user', $user)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Filter by tasks with/without subtasks
        // OPTIMIZATION: Use native SQL to get parent IDs, then filter with IN
        if ($onlyWithSubtasks) {
            $conn = $this->getEntityManager()->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?',
                [$user->getId()],
                [\Doctrine\DBAL\ParameterType::INTEGER]
            );
            $parentIds = $stmt->fetchFirstColumn();

            if (!empty($parentIds)) {
                $idsQb->andWhere('t.id IN (:parentTaskIds)')
                      ->setParameter('parentTaskIds', $parentIds);
            } else {
                return [];
            }
        }

        // Apply filters to ID query
        if ($filters) {
            $this->applyFilters($idsQb, $filters);
        }

        // Sort IDs
        $idsQb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
              ->setParameter('completedStatus', TaskStatus::COMPLETED)
              ->orderBy('completedOrder', 'ASC')
              ->addOrderBy('t.priority', 'DESC')
              ->addOrderBy('t.dueDate', 'ASC')
              ->addOrderBy('t.id', 'ASC');

        $taskIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        if (empty($taskIds)) {
            return [];
        }

        // Step 2: Load full data for these IDs with all joins (preserving order)
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.subtasks', 'st')
            ->leftJoin('t.recurrenceRule', 'rr')
            ->leftJoin('st.recurrenceRule', 'st_rr')
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('st')
            ->addSelect('rr')
            ->addSelect('st_rr')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds);

        // Preserve original sort order
        $qb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
           ->setParameter('completedStatus', TaskStatus::COMPLETED)
           ->orderBy('completedOrder', 'ASC')
           ->addOrderBy('t.priority', 'DESC')
           ->addOrderBy('t.dueDate', 'ASC')
           ->addOrderBy('t.id', 'ASC');

        $paginator = new Paginator($qb->getQuery(), false);
        return iterator_to_array($paginator);
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
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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
    public function findUpcomingTasks(User $user, int $days = 7, ?TaskFilterDto $filters = null, bool $onlyWithSubtasks = false): array
    {
        $tomorrow = new \DateTimeImmutable('tomorrow');
        $endDate = new \DateTimeImmutable("+{$days} days");

        // OPTIMIZED: Two-step approach to avoid slow ROW_NUMBER() OVER() queries
        // Step 1: Get task IDs with simple query (fast, no joins with subtasks)
        $idsQb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('(t.startDate >= :tomorrow AND t.startDate <= :endDate) OR (t.dueDate >= :tomorrow AND t.dueDate <= :endDate)')
            ->setParameter('user', $user)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('endDate', $endDate)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Filter by tasks with/without subtasks
        // OPTIMIZATION: Use native SQL to get parent IDs, then filter with IN
        if ($onlyWithSubtasks) {
            $conn = $this->getEntityManager()->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?',
                [$user->getId()],
                [\Doctrine\DBAL\ParameterType::INTEGER]
            );
            $parentIds = $stmt->fetchFirstColumn();

            if (!empty($parentIds)) {
                $idsQb->andWhere('t.id IN (:parentTaskIds)')
                      ->setParameter('parentTaskIds', $parentIds);
            } else {
                return [];
            }
        }

        // Apply filters to ID query
        if ($filters) {
            $this->applyFilters($idsQb, $filters);
        }

        // Sort and limit IDs
        $idsQb->addSelect('DATE(COALESCE(t.dueDate, t.startDate)) AS HIDDEN dateOnly')
              ->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
              ->setParameter('completedStatus', TaskStatus::COMPLETED)
              ->orderBy('dateOnly', 'ASC')
              ->addOrderBy('completedOrder', 'ASC')
              ->addOrderBy('t.priority', 'DESC')
              ->addOrderBy('t.id', 'ASC')
              ->setMaxResults(200);

        $taskIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        if (empty($taskIds)) {
            return [];
        }

        // Step 2: Load full data for these IDs with all joins (preserving order)
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.subtasks', 'st')
            ->leftJoin('t.recurrenceRule', 'rr')
            ->leftJoin('st.recurrenceRule', 'st_rr')
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('st')
            ->addSelect('rr')
            ->addSelect('st_rr')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds);

        // Preserve original sort order
        $qb->addSelect('DATE(COALESCE(t.dueDate, t.startDate)) AS HIDDEN dateOnly')
           ->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
           ->setParameter('completedStatus', TaskStatus::COMPLETED)
           ->orderBy('dateOnly', 'ASC')
           ->addOrderBy('completedOrder', 'ASC')
           ->addOrderBy('t.priority', 'DESC')
           ->addOrderBy('t.id', 'ASC');

        $paginator = new Paginator($qb->getQuery(), false);
        return iterator_to_array($paginator);
    }

    public function findActiveTasks(User $user, ?TaskFilterDto $filters = null, ?int $limit = null, ?int $offset = null, bool $onlyWithSubtasks = false): array
    {
        $todayStart = new \DateTimeImmutable('today');

        // OPTIMIZED: Two-step approach to avoid slow ROW_NUMBER() OVER() queries
        // Step 1: Get task IDs with simple query (fast, no joins with subtasks)
        $idsQb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('(
                (t.dueDate IS NOT NULL AND t.dueDate >= :todayStart) OR
                (t.startDate IS NOT NULL AND t.startDate >= :todayStart)
            )')
            ->setParameter('user', $user)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Filter by tasks with/without subtasks
        // OPTIMIZATION: Use native SQL to get parent IDs, then filter with IN
        // Native SQL query executes ONCE and PostgreSQL caches results + uses partial index
        if ($onlyWithSubtasks) {
            $conn = $this->getEntityManager()->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?',
                [$user->getId()],
                [\Doctrine\DBAL\ParameterType::INTEGER]
            );
            $parentIds = $stmt->fetchFirstColumn();

            if (!empty($parentIds)) {
                $idsQb->andWhere('t.id IN (:parentTaskIds)')
                      ->setParameter('parentTaskIds', $parentIds);
            } else {
                // No tasks with subtasks found - return empty result
                return [];
            }
        }

        // Apply filters to ID query
        if ($filters) {
            $this->applyFilters($idsQb, $filters);
        }

        // Sort and paginate IDs
        $idsQb->addSelect('DATE(COALESCE(t.dueDate, t.startDate)) AS HIDDEN dateOnly')
              ->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
              ->setParameter('completedStatus', TaskStatus::COMPLETED)
              ->orderBy('dateOnly', 'ASC')
              ->addOrderBy('completedOrder', 'ASC')
              ->addOrderBy('t.priority', 'DESC')
              ->addOrderBy('t.id', 'ASC');

        // Apply pagination to ID query
        if ($limit !== null) {
            $idsQb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $idsQb->setFirstResult($offset);
        }

        $taskIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        if (empty($taskIds)) {
            return [];
        }

        // Step 2: Load full data for these IDs with all joins (preserving order)
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.subtasks', 'st')
            ->leftJoin('t.recurrenceRule', 'rr')
            ->leftJoin('st.recurrenceRule', 'st_rr')
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('st')
            ->addSelect('rr')
            ->addSelect('st_rr')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds);

        // Preserve original sort order
        $qb->addSelect('DATE(COALESCE(t.dueDate, t.startDate)) AS HIDDEN dateOnly')
           ->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
           ->setParameter('completedStatus', TaskStatus::COMPLETED)
           ->orderBy('dateOnly', 'ASC')
           ->addOrderBy('completedOrder', 'ASC')
           ->addOrderBy('t.priority', 'DESC')
           ->addOrderBy('t.id', 'ASC');

        // Use Paginator with fetchJoinCollection=false since we already have exact IDs
        $paginator = new Paginator($qb->getQuery(), false);
        return iterator_to_array($paginator);
    }

    /**
     * Count total active tasks for pagination
     */
    public function countActiveTasks(User $user, ?TaskFilterDto $filters = null): int
    {
        $todayStart = new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')  // Exclude cancelled tasks
            ->andWhere('(
                (t.dueDate IS NOT NULL AND t.dueDate >= :todayStart) OR
                (t.startDate IS NOT NULL AND t.startDate >= :todayStart)
            )')
            ->setParameter('user', $user)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Apply filters
        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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
            ->leftJoin('t.user', 'u')
            ->addSelect('tag')
            ->addSelect('u')
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
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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
        $qb = $this->createQueryBuilder('t');
        
        $stats = $qb
            ->select('t.status, COUNT(t.id) as count')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->setParameter('user', $user)
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
        
        $result = [
            'total' => 0,
            TaskStatus::PENDING->value => 0,
            TaskStatus::IN_PROGRESS->value => 0,
            TaskStatus::COMPLETED->value => 0,
            TaskStatus::CANCELLED->value => 0,
            'overdue' => count($this->findOverdueTasks($user)),
        ];
        
        foreach ($stats as $stat) {
            $statusValue = $stat['status'] instanceof TaskStatus ? $stat['status']->value : $stat['status'];
            $result[$statusValue] = (int) $stat['count'];
            $result['total'] += (int) $stat['count'];
        }
        
        return $result;
    }

    /**
     * Find task with all nested subtasks loaded
     */
    public function findWithSubtasks(int $id): ?Task
    {
        $conn = $this->getEntityManager()->getConnection();

        // Recursive CTE to load all subtasks in ONE query
        $sql = "
            WITH RECURSIVE subtask_tree AS (
                -- Base case: get the main task
                SELECT t.* FROM task t WHERE t.id = :id
                UNION ALL
                -- Recursive case: get all subtasks
                SELECT t.* FROM task t
                INNER JOIN subtask_tree st ON t.parent_task_id = st.id
            )
            SELECT id FROM subtask_tree
        ";

        $taskIds = $conn->executeQuery($sql, ['id' => $id])->fetchFirstColumn();

        if (empty($taskIds)) {
            return null;
        }

        // Load all tasks + tags + user + recurrenceRule in ONE query using IN clause
        $tasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds)
            ->getQuery()
            ->getResult();

        // Find and return the main task (Doctrine will automatically populate subtasks collection)
        foreach ($tasks as $task) {
            if ($task->getId() === $id) {
                return $task;
            }
        }

        return null;
    }

    /**
     * @deprecated No longer needed - use findWithSubtasks() with CTE
     * Recursively load subtasks
     */
    private function loadSubtasksRecursively(Task $task): void
    {
        // This method is deprecated and no longer used
        // Kept for backward compatibility only
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
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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

    public function findOverdueByUserPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null, bool $onlyWithSubtasks = false): Paginator
    {
        // OPTIMIZED: Two-step approach to avoid slow ROW_NUMBER() OVER() queries
        // Step 1: Get task IDs with simple query (fast)
        $idsQb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('t.dueDate < :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Filter by tasks with/without subtasks
        // OPTIMIZATION: Use native SQL to get parent IDs, then filter with IN
        // Native SQL query executes ONCE and PostgreSQL caches results + uses partial index
        if ($onlyWithSubtasks) {
            $conn = $this->getEntityManager()->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?',
                [$user->getId()],
                [\Doctrine\DBAL\ParameterType::INTEGER]
            );
            $parentIds = $stmt->fetchFirstColumn();

            if (!empty($parentIds)) {
                $idsQb->andWhere('t.id IN (:parentTaskIds)')
                      ->setParameter('parentTaskIds', $parentIds);
            } else {
                // No tasks with subtasks found - return empty paginator
                $emptyQb = $this->createQueryBuilder('t')->where('1 = 0');
                return new Paginator($emptyQb->getQuery());
            }
        }

        // Apply filters to ID query
        if ($filters) {
            $this->applyFilters($idsQb, $filters);
        }

        // Sort and paginate IDs
        $idsQb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
              ->setParameter('completedStatus', TaskStatus::COMPLETED)
              ->orderBy('completedOrder', 'ASC')
              ->addOrderBy('t.dueDate', 'ASC')
              ->addOrderBy('t.priority', 'DESC')
              ->addOrderBy('t.id', 'ASC')
              ->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);

        $taskIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        if (empty($taskIds)) {
            // Return empty paginator
            $emptyQb = $this->createQueryBuilder('t')
                ->where('1 = 0');
            return new Paginator($emptyQb->getQuery());
        }

        // Step 2: Load full data for these IDs with all joins (preserving order)
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.subtasks', 'st')
            ->leftJoin('t.recurrenceRule', 'rr')
            ->leftJoin('st.recurrenceRule', 'st_rr')
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('st')
            ->addSelect('rr')
            ->addSelect('st_rr')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds);

        // Preserve original sort order using FIELD()
        $qb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
           ->setParameter('completedStatus', TaskStatus::COMPLETED)
           ->orderBy('completedOrder', 'ASC')
           ->addOrderBy('t.dueDate', 'ASC')
           ->addOrderBy('t.priority', 'DESC')
           ->addOrderBy('t.id', 'ASC');

        return new Paginator($qb->getQuery(), false);
    }

    public function findUnscheduledByUserPaginated(User $user, int $page, int $limit, ?TaskFilterDto $filters = null, bool $onlyWithSubtasks = false): Paginator
    {
        // OPTIMIZED: Two-step approach to avoid slow ROW_NUMBER() OVER() queries
        // Step 1: Get task IDs with simple query (fast)
        $idsQb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('t.dueDate IS NULL')
            ->setParameter('user', $user)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        // Filter by tasks with/without subtasks
        // OPTIMIZATION: Use native SQL to get parent IDs, then filter with IN
        // Native SQL query executes ONCE and PostgreSQL caches results + uses partial index
        if ($onlyWithSubtasks) {
            $conn = $this->getEntityManager()->getConnection();
            $stmt = $conn->executeQuery(
                'SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?',
                [$user->getId()],
                [\Doctrine\DBAL\ParameterType::INTEGER]
            );
            $parentIds = $stmt->fetchFirstColumn();

            if (!empty($parentIds)) {
                $idsQb->andWhere('t.id IN (:parentTaskIds)')
                      ->setParameter('parentTaskIds', $parentIds);
            } else {
                // No tasks with subtasks found - return empty paginator
                $emptyQb = $this->createQueryBuilder('t')->where('1 = 0');
                return new Paginator($emptyQb->getQuery());
            }
        }

        // Apply filters to ID query
        if ($filters) {
            $this->applyFilters($idsQb, $filters);
        }

        // Sort and paginate IDs
        $idsQb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
              ->setParameter('completedStatus', TaskStatus::COMPLETED)
              ->orderBy('completedOrder', 'ASC')
              ->addOrderBy('t.createdAt', 'DESC')
              ->addOrderBy('t.priority', 'DESC')
              ->addOrderBy('t.id', 'ASC')
              ->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);

        $taskIds = array_column($idsQb->getQuery()->getScalarResult(), 'id');

        if (empty($taskIds)) {
            // Return empty paginator
            $emptyQb = $this->createQueryBuilder('t')
                ->where('1 = 0');
            return new Paginator($emptyQb->getQuery());
        }

        // Step 2: Load full data for these IDs with all joins (preserving order)
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.subtasks', 'st')
            ->leftJoin('t.recurrenceRule', 'rr')
            ->leftJoin('st.recurrenceRule', 'st_rr')
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('st')
            ->addSelect('rr')
            ->addSelect('st_rr')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds);

        // Preserve original sort order
        $qb->addSelect('CASE WHEN t.status = :completedStatus THEN 1 ELSE 0 END AS HIDDEN completedOrder')
           ->setParameter('completedStatus', TaskStatus::COMPLETED)
           ->orderBy('completedOrder', 'ASC')
           ->addOrderBy('t.createdAt', 'DESC')
           ->addOrderBy('t.priority', 'DESC')
           ->addOrderBy('t.id', 'ASC');

        return new Paginator($qb->getQuery(), false);
    }

    /**
     * Count total overdue tasks (for pagination)
     */
    public function countOverdueByUser(User $user, ?TaskFilterDto $filters = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('t.dueDate < :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count total unscheduled tasks (for pagination)
     */
    public function countUnscheduledByUser(User $user, ?TaskFilterDto $filters = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :cancelledStatus')
            ->andWhere('t.dueDate IS NULL')
            ->setParameter('user', $user)
            ->setParameter('cancelledStatus', TaskStatus::CANCELLED);

        if ($filters) {
            $this->applyFilters($qb, $filters);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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
        // Task is included if at least one of its dates (dueDate or startDate) falls within the range [dateFrom, dateTo]
        $dateFrom = $filters->getDateFrom();
        $dateTo = $filters->getDateTo();
        
        if ($dateFrom !== null || $dateTo !== null) {
            $dateCondition = [];
            $params = [];
            
            if ($dateFrom !== null && $dateTo !== null) {
                // Both boundaries specified: task must have at least one date in range [dateFrom, dateTo]
                $dateFromObj = new \DateTimeImmutable($dateFrom);
                $dateToObj = new \DateTimeImmutable($dateTo . ' 23:59:59');
                
                // Task is included if:
                // 1. dueDate is in range [dateFrom, dateTo], OR
                // 2. startDate is in range [dateFrom, dateTo]
                $dateCondition[] = '(
                    (t.dueDate IS NOT NULL AND t.dueDate >= :filterDateFrom AND t.dueDate <= :filterDateTo) OR
                    (t.startDate IS NOT NULL AND t.startDate >= :filterDateFrom AND t.startDate <= :filterDateTo)
                )';
                $params['filterDateFrom'] = $dateFromObj;
                $params['filterDateTo'] = $dateToObj;
            } elseif ($dateFrom !== null) {
                // Only lower boundary: task must have at least one date >= dateFrom
                $dateFromObj = new \DateTimeImmutable($dateFrom);
                $dateCondition[] = '(t.dueDate >= :filterDateFrom OR t.startDate >= :filterDateFrom)';
                $params['filterDateFrom'] = $dateFromObj;
            } elseif ($dateTo !== null) {
                // Only upper boundary: task must have at least one date <= dateTo
                $dateToObj = new \DateTimeImmutable($dateTo . ' 23:59:59');
                $dateCondition[] = '(t.dueDate <= :filterDateTo OR t.startDate <= :filterDateTo)';
                $params['filterDateTo'] = $dateToObj;
            }
            
            $qb->andWhere(implode(' AND ', $dateCondition));
            
            foreach ($params as $key => $value) {
                $qb->setParameter($key, $value);
            }
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
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
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
     * Get average completion time in days
     */
    public function getAverageCompletionTime(User $user): float
    {
        $tasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->andWhere('t.createdAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (count($tasks) === 0) {
            return 0;
        }

        $totalDays = 0;
        foreach ($tasks as $task) {
            $created = $task->getCreatedAt();
            $completed = $task->getCompletedAt();
            if ($created && $completed) {
                $diff = $completed->diff($created);
                $totalDays += $diff->days;
            }
        }

        return round($totalDays / count($tasks), 1);
    }

    /**
     * Get on-time completion rate (percentage)
     */
    public function getOnTimeCompletionRate(User $user): int
    {
        $qb = $this->createQueryBuilder('t');
        
        $totalWithDueDate = $qb
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        if ($totalWithDueDate == 0) {
            return 100;
        }
        
        $qb = $this->createQueryBuilder('t');
        $onTime = $qb
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->andWhere('t.completedAt <= t.dueDate')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int)round(($onTime / $totalWithDueDate) * 100);
    }

    /**
     * Get most productive day of week
     */
    public function getMostProductiveDay(User $user): ?string
    {
        $tasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (count($tasks) === 0) {
            return null;
        }

        $dayCount = [];
        foreach ($tasks as $task) {
            if ($task->getCompletedAt()) {
                $dayName = $task->getCompletedAt()->format('l'); // Monday, Tuesday, etc.
                $dayCount[$dayName] = ($dayCount[$dayName] ?? 0) + 1;
            }
        }

        if (empty($dayCount)) {
            return null;
        }

        arsort($dayCount);
        return array_key_first($dayCount);
    }

    /**
     * Get completion timeline data for chart
     */
    public function getCompletionTimelineData(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $dates = [];
        $created = [];
        $completed = [];
        $overdue = [];
        
        $current = \DateTimeImmutable::createFromInterface($start);
        $endDate = \DateTimeImmutable::createFromInterface($end);
        
        while ($current <= $endDate) {
            $dayStart = $current->setTime(0, 0);
            $dayEnd = $current->setTime(23, 59, 59);
            
            $dates[] = $current->format('Y-m-d');
            
            // Created tasks
            $createdCount = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.createdAt BETWEEN :start AND :end')
                ->setParameter('user', $user)
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();
            
            // Completed tasks
            $completedCount = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.completedAt BETWEEN :start AND :end')
                ->setParameter('user', $user)
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();
            
            // Overdue tasks
            $overdueCount = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.dueDate < :date')
                ->andWhere('t.status != :completed')
                ->andWhere('t.createdAt <= :date')
                ->setParameter('user', $user)
                ->setParameter('date', $dayEnd)
                ->setParameter('completed', TaskStatus::COMPLETED)
                ->getQuery()
                ->getSingleScalarResult();
            
            $created[] = (int)$createdCount;
            $completed[] = (int)$completedCount;
            $overdue[] = (int)$overdueCount;
            
            $current = $current->modify('+1 day');
        }
        
        return [
            'dates' => $dates,
            'created' => $created,
            'completed' => $completed,
            'overdue' => $overdue
        ];
    }

    /**
     * Get priority breakdown with completion stats
     */
    public function getPriorityBreakdown(User $user): array
    {
        $result = [];
        
        foreach (\App\Enum\TaskPriority::cases() as $priority) {
            $total = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.priority = :priority')
                ->andWhere('t.isArchived = false')
                ->setParameter('user', $user)
                ->setParameter('priority', $priority)
                ->getQuery()
                ->getSingleScalarResult();
            
            $completed = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.priority = :priority')
                ->andWhere('t.status = :completedStatus')
                ->andWhere('t.isArchived = false')
                ->setParameter('user', $user)
                ->setParameter('priority', $priority)
                ->setParameter('completedStatus', TaskStatus::COMPLETED)
                ->getQuery()
                ->getSingleScalarResult();
            
            $inProgress = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.parentTask IS NULL')
                ->andWhere('t.priority = :priority')
                ->andWhere('t.status = :inProgressStatus')
                ->andWhere('t.isArchived = false')
                ->setParameter('user', $user)
                ->setParameter('priority', $priority)
                ->setParameter('inProgressStatus', TaskStatus::IN_PROGRESS)
                ->getQuery()
                ->getSingleScalarResult();
            
            $result[strtolower($priority->value)] = [
                'total' => (int)$total,
                'completed' => (int)$completed,
                'inProgress' => (int)$inProgress,
                'pending' => (int)$total - (int)$completed - (int)$inProgress
            ];
        }
        
        return $result;
    }

    /**
     * Get productivity heatmap (GitHub-style)
     */
    public function getProductivityHeatmap(User $user, int $year): array
    {
        $startDate = new \DateTimeImmutable("{$year}-01-01");
        $endDate = new \DateTimeImmutable("{$year}-12-31");

        $qb = $this->createQueryBuilder('t');
        $tasks = $qb
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $heatmap = [];
        foreach ($tasks as $task) {
            if ($task->getCompletedAt()) {
                $date = $task->getCompletedAt()->format('Y-m-d');
                $heatmap[$date] = ($heatmap[$date] ?? 0) + 1;
            }
        }

        return $heatmap;
    }

    /**
     * Get weekday productivity (Monday-Sunday)
     */
    public function getWeekdayProductivity(User $user): array
    {
        $tasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $days = ['Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0];

        foreach ($tasks as $task) {
            if ($task->getCompletedAt()) {
                $dayName = $task->getCompletedAt()->format('l'); // Monday, Tuesday, etc.
                if (isset($days[$dayName])) {
                    $days[$dayName]++;
                }
            }
        }

        return $days;
    }

    /**
     * Get most productive hour of day
     */
    public function getMostProductiveHour(User $user): ?int
    {
        $tasks = $this->createQueryBuilder('t')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.recurrenceRule', 'recurrenceRule') // FIX N+1: Eager load recurrence rules
            ->addSelect('tag')
            ->addSelect('u')
            ->addSelect('recurrenceRule') // FIX N+1: Include recurrence rules in SELECT
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        if (count($tasks) === 0) {
            return null;
        }

        $hourCount = [];
        foreach ($tasks as $task) {
            if ($task->getCompletedAt()) {
                $hour = (int)$task->getCompletedAt()->format('G'); // 0-23
                $hourCount[$hour] = ($hourCount[$hour] ?? 0) + 1;
            }
        }

        if (empty($hourCount)) {
            return null;
        }

        arsort($hourCount);
        return array_key_first($hourCount);
    }

    /**
     * Get tag completion statistics
     */
    public function getTagCompletionStats(User $user, int $tagId): array
    {
        $total = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.tags', 'tag')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('tag.id = :tagId')
            ->setParameter('user', $user)
            ->setParameter('tagId', $tagId)
            ->getQuery()
            ->getSingleScalarResult();
        
        $completed = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.tags', 'tag')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('tag.id = :tagId')
            ->andWhere('t.status = :completedStatus')
            ->setParameter('user', $user)
            ->setParameter('tagId', $tagId)
            ->setParameter('completedStatus', TaskStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
        
        $completionRate = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
        
        return [
            'total' => (int)$total,
            'completed' => (int)$completed,
            'completionRate' => $completionRate
        ];
    }
}
