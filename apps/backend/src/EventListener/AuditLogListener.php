<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class AuditLogListener
{
    private const EXCLUDED_ENTITIES = [
        AuditLog::class,
    ];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logAction('CREATE', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logAction('UPDATE', $args->getObject(), $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($args->getObject()));
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->logAction('DELETE', $args->getObject());
    }

    private function logAction(string $action, object $entity, array $changeSet = []): void
    {
        // Skip logging for excluded entities
        if ($this->isExcludedEntity($entity)) {
            return;
        }

        // Only log admin actions (from /admin routes)
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        try {
            $auditLog = new AuditLog();
            $auditLog->setUser($this->security->getUser());
            $auditLog->setAction($action);
            $auditLog->setEntityType($this->getEntityShortName($entity));

            if (method_exists($entity, 'getId')) {
                $auditLog->setEntityId($entity->getId());
            }

            if ($action === 'UPDATE' && !empty($changeSet)) {
                $auditLog->setOldData($this->serializeChangeSet($changeSet, true));
                $auditLog->setNewData($this->serializeChangeSet($changeSet, false));
            } else {
                $auditLog->setNewData($this->serializeEntity($entity));
            }

            $auditLog->setMetadata([
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'route' => $request->attributes->get('_route'),
                'method' => $request->getMethod(),
            ]);

            $em = $this->doctrine->getManager();
            $em->persist($auditLog);
            $em->flush();
        } catch (\Throwable $e) {
            // Silently fail to prevent breaking the main operation
            // In production, you might want to log this to a separate error log
            error_log(sprintf('AuditLog failed: %s', $e->getMessage()));
        }
    }

    private function isExcludedEntity(object $entity): bool
    {
        foreach (self::EXCLUDED_ENTITIES as $excludedClass) {
            if ($entity instanceof $excludedClass) {
                return true;
            }
        }

        return false;
    }

    private function getEntityShortName(object $entity): string
    {
        return (new \ReflectionClass($entity))->getShortName();
    }

    private function serializeEntity(object $entity): array
    {
        try {
            $normalized = $this->normalizer->normalize($entity, null, [
                'groups' => ['audit'],
                'circular_reference_handler' => function ($object) {
                    return method_exists($object, 'getId') ? $object->getId() : null;
                },
            ]);

            return is_array($normalized) ? $normalized : [];
        } catch (\Throwable $e) {
            // Fallback to basic serialization
            return $this->basicSerialize($entity);
        }
    }

    private function serializeChangeSet(array $changeSet, bool $oldValues): array
    {
        $result = [];

        foreach ($changeSet as $field => $values) {
            $value = $oldValues ? $values[0] : $values[1];

            if ($value instanceof \DateTimeInterface) {
                $result[$field] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $result[$field] = method_exists($value, 'getId')
                    ? sprintf('%s#%s', $this->getEntityShortName($value), $value->getId())
                    : $this->getEntityShortName($value);
            } elseif (is_array($value)) {
                $result[$field] = json_encode($value);
            } else {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    private function basicSerialize(object $entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            if ($value instanceof \DateTimeInterface) {
                $data[$property->getName()] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, 'getId')) {
                $data[$property->getName()] = sprintf('%s#%s', $this->getEntityShortName($value), $value->getId());
            } elseif (!is_object($value) && !is_resource($value)) {
                $data[$property->getName()] = $value;
            }
        }

        return $data;
    }
}
