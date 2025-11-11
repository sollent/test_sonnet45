<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\AuditLog;
use DateTimeImmutable;

/**
 * @extends AbstractRepository<AuditLog>
 *
 * @method AuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuditLog[]    findAll()
 * @method AuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuditLogRepository extends AbstractRepository
{
    public function save(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Delete all expired audit logs older than specified days
     */
    public function deleteOlderThan(int $days): int
    {
        $date = new DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('al')
            ->delete()
            ->where('al.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    protected function getEntityClass(): string
    {
        return AuditLog::class;
    }
}
