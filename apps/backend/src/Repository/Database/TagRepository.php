<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 *
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Find all tags for a user, ordered by usage count
     *
     * @return Tag[]
     */
    public function findUserTags(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.usageCount', 'DESC')
            ->addOrderBy('t.name', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find tag by name for a user (case-insensitive)
     */
    public function findByNameAndUser(string $name, User $user): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->where('LOWER(t.name) = :name')
            ->andWhere('t.user = :user')
            ->setParameter('name', strtolower($name))
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find or create tags by names for a user
     *
     * @param string[] $names
     *
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names, User $user): array
    {
        $names = array_unique(array_filter(array_map('trim', $names)));

        if (empty($names)) {
            return [];
        }

        // Find existing tags in one query
        $existingTags = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.name IN (:names)')
            ->setParameter('user', $user)
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();

        $existingTagNames = array_map(fn (Tag $tag) => $tag->getName(), $existingTags);

        $tagsToCreateNames = array_diff($names, $existingTagNames);
        $newTags = [];

        if (!empty($tagsToCreateNames)) {
            $colors = [
                '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                '#8B5CF6', '#EC4899', '#14B8A6', '#F97316',
            ];
            $colorIndex = 0;

            foreach ($tagsToCreateNames as $name) {
                $tag = new Tag();
                $tag->setName($name)
                    ->setUser($user)
                    ->setColor($colors[$colorIndex % count($colors)]);

                // Persist, but don't flush yet
                $this->getEntityManager()->persist($tag);
                $newTags[] = $tag;
                $colorIndex++;
            }

            // Flush newly created tags so subsequent calls can find them
            $this->getEntityManager()->flush();
        }

        return array_merge($existingTags, $newTags);
    }

    /**
     * Search tags by name for a user
     *
     * @return Tag[]
     */
    public function searchTags(User $user, string $query): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('LOWER(t.name) LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most used tags for a user
     *
     * @return Tag[]
     */
    public function getMostUsedTags(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.usageCount > 0')
            ->setParameter('user', $user)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Update usage counts for all user tags
     */
    public function updateUsageCounts(User $user): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            UPDATE tag t
            SET usage_count = (
                SELECT COUNT(*)
                FROM task_tags tt
                JOIN task ta ON ta.id = tt.task_id
                WHERE tt.tag_id = t.id
                AND ta.user_id = :userId
            )
            WHERE t.user_id = :userId
        ';

        $conn->executeStatement($sql, ['userId' => $user->getId()]);
    }

    public function save(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
