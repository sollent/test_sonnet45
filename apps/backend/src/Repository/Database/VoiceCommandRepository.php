<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\User;
use App\Entity\VoiceCommand;
use App\ValueObject\CommandStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository для голосовых команд
 *
 * Предоставляет методы для работы с голосовыми командами в БД
 * Следует паттерну Repository и принципу SRP
 *
 * @method VoiceCommand|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoiceCommand|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoiceCommand[]    findAll()
 * @method VoiceCommand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoiceCommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoiceCommand::class);
    }

    /**
     * Сохранить голосовую команду
     */
    public function save(VoiceCommand $command, bool $flush = true): void
    {
        $this->_em->persist($command);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Удалить голосовую команду
     */
    public function remove(VoiceCommand $command, bool $flush = true): void
    {
        $this->_em->remove($command);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Найти команды пользователя
     *
     * @return VoiceCommand[]
     */
    public function findByUser(
        User $user,
        ?CommandStatus $status = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('vc')
            ->where('vc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('vc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($status !== null) {
            $qb->andWhere('vc.status = :status')
                ->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Найти незавершенные команды пользователя
     *
     * @return VoiceCommand[]
     */
    public function findPendingByUser(User $user): array
    {
        return $this->createQueryBuilder('vc')
            ->where('vc.user = :user')
            ->andWhere('vc.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                CommandStatus::PENDING->value,
                CommandStatus::PROCESSING->value,
                CommandStatus::EXECUTING->value,
            ])
            ->orderBy('vc.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти последнюю команду пользователя
     */
    public function findLastByUser(User $user): ?VoiceCommand
    {
        return $this->createQueryBuilder('vc')
            ->where('vc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('vc.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Найти команды по статусу
     *
     * @return VoiceCommand[]
     */
    public function findByStatus(CommandStatus $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('vc')
            ->where('vc.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('vc.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти застрявшие команды (processing более N минут)
     *
     * @param int $minutes Количество минут
     *
     * @return VoiceCommand[]
     */
    public function findStuckCommands(int $minutes = 5): array
    {
        $threshold = new DateTimeImmutable(sprintf('-%d minutes', $minutes));

        return $this->createQueryBuilder('vc')
            ->where('vc.status IN (:statuses)')
            ->andWhere('vc.processingStartedAt IS NOT NULL')
            ->andWhere('vc.processingStartedAt < :threshold')
            ->setParameter('statuses', [
                CommandStatus::PROCESSING->value,
                CommandStatus::EXECUTING->value,
            ])
            ->setParameter('threshold', $threshold)
            ->orderBy('vc.processingStartedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Получить статистику по командам пользователя
     */
    public function getUserStatistics(User $user): array
    {
        $qb = $this->createQueryBuilder('vc');

        $stats = $qb->select([
            'vc.status as status',
            'COUNT(vc.id) as count',
            'AVG(vc.processingDurationMs) as avg_duration',
        ])
            ->where('vc.user = :user')
            ->setParameter('user', $user)
            ->groupBy('vc.status')
            ->getQuery()
            ->getArrayResult();

        // Форматируем результат
        $result = [
            'total'               => 0,
            'by_status'           => [],
            'average_duration_ms' => 0,
        ];

        $totalDuration = 0;
        $countWithDuration = 0;

        foreach ($stats as $row) {
            $status = $row['status'];
            $count = (int) $row['count'];
            $avgDuration = $row['avg_duration'];

            $result['by_status'][$status] = $count;
            $result['total'] += $count;

            if ($avgDuration !== null) {
                $totalDuration += $avgDuration * $count;
                $countWithDuration += $count;
            }
        }

        if ($countWithDuration > 0) {
            $result['average_duration_ms'] = round($totalDuration / $countWithDuration);
        }

        return $result;
    }

    /**
     * Найти команды за период
     */
    public function findByDateRange(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?User $user = null,
    ): array {
        $qb = $this->createQueryBuilder('vc')
            ->where('vc.createdAt >= :from')
            ->andWhere('vc.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('vc.createdAt', 'DESC');

        if ($user !== null) {
            $qb->andWhere('vc.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Получить QueryBuilder для пагинации
     */
    public function createPaginationQueryBuilder(
        ?User $user = null,
        ?CommandStatus $status = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('vc')
            ->orderBy('vc.createdAt', 'DESC');

        if ($user !== null) {
            $qb->andWhere('vc.user = :user')
                ->setParameter('user', $user);
        }

        if ($status !== null) {
            $qb->andWhere('vc.status = :status')
                ->setParameter('status', $status->value);
        }

        return $qb;
    }

    /**
     * Очистить старые завершенные команды
     *
     * @param int $daysOld Возраст команд в днях
     *
     * @return int Количество удаленных записей
     */
    public function cleanupOldCommands(int $daysOld = 30): int
    {
        $threshold = new DateTimeImmutable(sprintf('-%d days', $daysOld));

        return $this->createQueryBuilder('vc')
            ->delete()
            ->where('vc.status IN (:statuses)')
            ->andWhere('vc.completedAt < :threshold')
            ->setParameter('statuses', [
                CommandStatus::COMPLETED->value,
                CommandStatus::FAILED->value,
            ])
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }
}
