<?php

namespace App\Doctrine\Listener;

use App\Entity\Contracts\DomainEventsInterface;
use App\Entity\Contracts\VisibilityInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatch events when entities that implement {@link DomainEventsInterface}
 * are persisted, removed, or updated.
 */
final class DomainEventsListener implements EventSubscriber {
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    private $dispatching = false;
    private $cachedOriginalEntities = [];

    public function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function getSubscribedEvents(): array {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::preUpdate,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void {
        $entity = $args->getEntity();

        if ($entity instanceof DomainEventsInterface) {
            $this->dispatch($entity->onCreate());
        }
    }

    public function postRemove(LifecycleEventArgs $args): void {
        $entity = $args->getEntity();

        if ($entity instanceof DomainEventsInterface) {
            $this->dispatch($entity->onDelete());
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void {
        $entity = $args->getEntity();

        if ($entity instanceof DomainEventsInterface) {
            $previous = clone $entity;
            $rPrevious = new \ReflectionClass($this->getClassName($args));

            foreach ($args->getEntityChangeSet() as $field => $value) {
                $rField = $rPrevious->getProperty($field);
                $rField->setAccessible(true);
                $rField->setValue($previous, $value[0]);
                $rField->setAccessible(false);
            }

            $this->cachedOriginalEntities[spl_object_id($entity)] = $previous;
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void {
        $entity = $args->getEntity();

        if ($entity instanceof DomainEventsInterface) {
            $key = spl_object_id($entity);
            $previous = $this->cachedOriginalEntities[$key] ?? null;

            if (!$previous) {
                throw new \RuntimeException('Original entity was not cached');
            }

            unset($this->cachedOriginalEntities[$key]);

            $this->dispatch($entity->onUpdate($previous));

            if (
                $entity instanceof VisibilityInterface &&
                $entity->getVisibility() === VisibilityInterface::VISIBILITY_SOFT_DELETED &&
                $previous->getVisibility() !== VisibilityInterface::VISIBILITY_SOFT_DELETED
            ) {
                $this->dispatch($entity->onDelete());
            }
        }
    }

    private function getClassName(LifecycleEventArgs $args): string {
        $proxyClassName = \get_class($args->getEntity());

        return $args->getEntityManager()->getClassMetadata($proxyClassName)->getName();
    }

    private function dispatch(object $event): void {
        if (!$this->dispatching) {
            try {
                $this->dispatching = true;
                $this->dispatcher->dispatch($event);
            } finally {
                $this->dispatching = false;
            }
        }
    }
}
