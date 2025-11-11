<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Task;
use App\Entity\TaskAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskAttachment>
 */
class TaskAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskAttachment::class);
    }

    /**
     * Find all attachments for a task
     *
     * @return TaskAttachment[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.task = :task')
            ->setParameter('task', $task)
            ->orderBy('a.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find images for a task
     *
     * @return TaskAttachment[]
     */
    public function findImagesByTask(Task $task): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.task = :task')
            ->andWhere('a.fileType = :type')
            ->setParameter('task', $task)
            ->setParameter('type', 'image')
            ->orderBy('a.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TaskAttachment $attachment): void
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }

    public function remove(TaskAttachment $attachment): void
    {
        $this->getEntityManager()->remove($attachment);
        $this->getEntityManager()->flush();
    }
}
