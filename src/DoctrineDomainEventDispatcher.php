<?php

namespace Vanio\DoctrineDomainEvents;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

#[AsDoctrineListener(event: Events::postFlush, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
class DoctrineDomainEventDispatcher
{
    private EntityManager $entityManager;

    /** @var EventProvider[]  */
    private array $eventProviders = [];

    public function postFlush(PostFlushEventArgs $event): void
    {
        $this->entityManager = $event->getObjectManager();
        $eventsByOrder = [];

        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                $this->keepEventProviders($entity);
            }
        }

        foreach ($this->eventProviders as $eventProvider) {
            foreach ($eventProvider->popEvents() as $order => $event) {
                $eventsByOrder[$order][] = $event;
            }
        }

        $this->clearChangeSets();
        ksort($eventsByOrder);

        foreach ($eventsByOrder as $events) {
            foreach ($events as $event) {
                $this->dispatchEvent($event->name(), $event);
            }
        }

        $this->eventProviders = [];
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->keepEventProviders($event->getObject());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param object $entity
     */
    private function keepEventProviders(object $entity): void
    {
        if ($entity instanceof EventProvider) {
            $this->eventProviders[] = $entity;
        }
    }

    private function dispatchEvent(string $eventName, ?EventArgs $event = null): void
    {
        $this->entityManager->getEventManager()->dispatchEvent($eventName, $event);
    }

    /**
     * This dirty hack provides an ability to safely commit the unit of work inside postFlush event.
     */
    private function clearChangeSets(): void
    {
        $clearChangeSets = function () {
            // phpcs:disable
            $this->entityInsertions = $this->entityUpdates
                = $this->entityDeletions
                = $this->extraUpdates
                = $this->entityChangeSets
                = $this->collectionUpdates
                = $this->collectionDeletions
                = $this->visitedCollections
                = $this->scheduledForSynchronization
                = $this->orphanRemovals
                = [];
            // phpcs:enable
        };
        $clearChangeSets = $clearChangeSets->bindTo($this->entityManager->getUnitOfWork(), UnitOfWork::class);
        $clearChangeSets();
    }
}
