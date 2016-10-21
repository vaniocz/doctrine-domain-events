<?php
namespace Vanio\DoctrineDomainEvents;

interface EventProvider
{
    /**
     * @return DomainEvent[]
     */
    public function popEvents(): array;
}
