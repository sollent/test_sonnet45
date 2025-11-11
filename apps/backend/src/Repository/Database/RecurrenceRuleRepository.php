<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\RecurrenceRule;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurrenceRule>
 */
class RecurrenceRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurrenceRule::class);
    }

    public function save(RecurrenceRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RecurrenceRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active recurrence rules that need to generate new tasks
     *
     * @return RecurrenceRule[]
     */
    public function findActiveRulesToProcess(?DateTime $now = null): array
    {
        $now = $now ?? new DateTime();

        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->andWhere('r.nextOccurrenceDate <= :now')
            ->andWhere('(r.endDate IS NULL OR r.endDate >= :now)')
            ->andWhere('(r.maxOccurrences IS NULL OR r.currentOccurrences < r.maxOccurrences)')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recurrence rules by user
     *
     * @return RecurrenceRule[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active recurrence rules by user
     *
     * @return RecurrenceRule[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.createdBy = :user')
            ->andWhere('r.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('r.nextOccurrenceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Deactivate expired rules
     */
    public function deactivateExpiredRules(?DateTime $now = null): int
    {
        $now = $now ?? new DateTime();

        $qb = $this->createQueryBuilder('r')
            ->update()
            ->set('r.isActive', ':inactive')
            ->where('r.isActive = :active')
            ->andWhere('(r.endDate IS NOT NULL AND r.endDate < :now) OR (r.maxOccurrences IS NOT NULL AND r.currentOccurrences >= r.maxOccurrences)')
            ->setParameter('inactive', false)
            ->setParameter('active', true)
            ->setParameter('now', $now);

        return $qb->getQuery()->execute();
    }
}
