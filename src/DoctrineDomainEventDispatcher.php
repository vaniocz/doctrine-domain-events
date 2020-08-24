<?php
namespace Vanio\DoctrineDomainEvents;

use Doctrine\Common\EventArgs;
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
    private $eventProviders = [];

    public function postFlush(PostFlushEventArgs $event)
    {
        $this->entityManager = $event->getEntityManager();
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

    public function postRemove(LifecycleEventArgs $event)
    {
        $this->keepEventProviders($event->getEntity());
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Events::postFlush, Events::postRemove];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param object $entity
     */
    private function keepEventProviders($entity)
    {
        if ($entity instanceof EventProvider) {
            $this->eventProviders[] = $entity;
        }
    }

    private function dispatchEvent(string $eventName, EventArgs $event = null)
    {
        $this->entityManager->getEventManager()->dispatchEvent($eventName, $event);
    }

    /**
     * This dirty hack provides an ability to safely commit the unit of work inside postFlush event.
     */
    private function clearChangeSets()
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
