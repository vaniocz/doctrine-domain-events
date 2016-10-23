<?php
namespace Vanio\DoctrineDomainEvents;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

class DoctrineDomainEventDispatcher implements EventSubscriber
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EventProvider[]  */
    private $eventProviders;

    public function postFlush(PostFlushEventArgs $event)
    {
        $this->entityManager = $event->getEntityManager();
        $this->eventProviders = [];
        $this->dispatchEventsOnTransactionEnd();
    }

    public function postRemove(LifecycleEventArgs $event)
    {
        $this->entityManager = $event->getEntityManager();
        $this->keepEventProviders($event->getEntity());
        $this->dispatchEventsOnTransactionEnd();
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postFlush, Events::postRemove];
    }

    /**
     * @param object $entity
     */
    private function keepEventProviders($entity)
    {
        if ($entity instanceof EventProvider) {
            $this->eventProviders[] = $entity;
        }
    }

    private function dispatchEventsOnTransactionEnd()
    {
        if (!$this->isTransactionEnd()) {
            return;
        }

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

        if (!$eventsByOrder) {
            return;
        }

        $this->clearChangeSets();
        ksort($eventsByOrder);

        foreach ($eventsByOrder as $events) {
            foreach ($events as $event) {
                $this->entityManager->getEventManager()->dispatchEvent($event->name(), $event);
            }
        }
    }

    private function isTransactionEnd(): bool
    {
        return !$this->entityManager->getUnitOfWork()->getScheduledEntityDeletions();
    }

    /**
     * This dirty hack provides an ability to safely commit the unit of work inside postFlush event.
     */
    private function clearChangeSets()
    {
        $clearChangeSets = function () {
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
        };
        $clearChangeSets = $clearChangeSets->bindTo($this->entityManager->getUnitOfWork(), UnitOfWork::class);
        $clearChangeSets();
    }
}
