<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\MediaObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaObject>
 */
class MediaObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaObject::class);
    }

    public function save(MediaObject $mediaObject, bool $flush = true): void
    {
        $this->getEntityManager()->persist($mediaObject);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MediaObject $mediaObject, bool $flush = true): void
    {
        $this->getEntityManager()->remove($mediaObject);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
