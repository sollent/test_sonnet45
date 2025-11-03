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

        // Apply default status filter only if no status filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        if (!$statuses || empty($statuses)) {
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

        // Apply default status filter only if no status filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        if (!$statuses || empty($statuses)) {
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

        // Apply default status filter only if no status filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        if (!$statuses || empty($statuses)) {
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

        // Apply default status filter only if no status filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        if (!$statuses || empty($statuses)) {
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

        // Apply default status filter only if no status filter is provided
        $statuses = $filters ? $filters->getStatuses() : null;
        if (!$statuses || empty($statuses)) {
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
}
