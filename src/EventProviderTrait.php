<?php
namespace Vanio\DoctrineDomainEvents;

trait EventProviderTrait
{
    /** @var DomainEvent[] */
    private $_events = [];

    /**
     * @return DomainEvent[]
     */
    public function popEvents(): array
    {
        $events = $this->_events;
        $this->_events = [];

        return $events;
    }

    /**
     * @param string|DomainEvent $event
     * @param array $properties
     * @throws \InvalidArgumentException
     */
    protected function raise($event, array $properties = [])
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

        $this->_events[$order++] = $event;
    }
}
