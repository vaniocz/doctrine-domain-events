<?php

namespace Vanio\DoctrineDomainEvents;

trait EventProviderTrait
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * @return DomainEvent[]
     */
    final public function popEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * @param mixed[] $properties
     */
    protected function raise(string|DomainEvent $event, array $properties = []): void
    {
        static $order = 0;

        if (is_string($event)) {
            $event = new DomainEvent($event, $properties);
        } elseif (!$event instanceof DomainEvent) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid domain event. Can only raise instances of "%s".',
                DomainEvent::class
            ));
        }

        $this->domainEvents[$order++] = $event;
    }
}
