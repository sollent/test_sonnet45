<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findTodayTasks(User $user): array
    {
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = new \DateTimeImmutable('today 23:59:59');

        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :completedStatus')
            ->andWhere(
                '(t.dueDate BETWEEN :todayStart AND :todayEnd) OR (t.startDate BETWEEN :todayStart AND :todayEnd)'
            )
            ->setParameter('user', $user)
            ->setParameter('completedStatus', TaskStatus::COMPLETED)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
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
    public function findUpcomingTasks(User $user, int $days = 7): array
    {
        $tomorrow = new \DateTimeImmutable('tomorrow');
        $endDate = new \DateTimeImmutable("+{$days} days");

        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('t.status != :completed')
            ->andWhere('(t.startDate >= :tomorrow AND t.startDate <= :endDate) OR (t.dueDate >= :tomorrow AND t.dueDate <= :endDate)')
            ->setParameter('user', $user)
            ->setParameter('completed', TaskStatus::COMPLETED)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
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
    public function searchTasks(User $user, string $query): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.parentTask IS NULL')
            ->andWhere('t.isArchived = false')
            ->andWhere('(LOWER(t.title) LIKE :query OR LOWER(t.description) LIKE :query)')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('t.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
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
}
