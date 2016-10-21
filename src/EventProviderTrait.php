<?php
namespace Vanio\DoctrineDomainEvents;

trait EventProviderTrait
{
    /** @var int */
    private static $order = 0;

    /** @var DomainEvent[] */
    private $events = [];

    /**
     * @return DomainEvent[]
     */
    public function popEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    /**
     * @param string|DomainEvent $event
     * @param array $properties
     * @throws \InvalidArgumentException
     */
    protected function raise($event, array $properties = [])
    {
        if (is_string($event)) {
            $event = new DomainEvent($event, $properties);
        } elseif (!$event instanceof DomainEvent) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid domain event. Can only raise instances of "%s".',
                DomainEvent::class
            ));
        }

        $this->events[self::$order++] = $event;
    }
}
