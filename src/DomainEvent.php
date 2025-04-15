<?php

namespace Vanio\DoctrineDomainEvents;

use Doctrine\Common\EventArgs;

class DomainEvent extends EventArgs
{
    /**
     * @param mixed[] $properties
     */
    public function __construct(private string $name, private array $properties = [])
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function __get(string $property): mixed
    {
        if (!array_key_exists($property, $this->properties)) {
            throw new \RuntimeException(sprintf(
                'Property "%s" does not exist on domain event "%s".',
                $property,
                $this->name
            ));
        }

        return $this->properties[$property];
    }
}
