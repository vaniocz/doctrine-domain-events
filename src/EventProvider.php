<?php
namespace Vanio\DoctrineDomainEvents;

interface EventProvider
{
    /**
     * @return DomainEvent[]
     */
    function popEvents(): array;
}
