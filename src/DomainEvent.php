<?php
namespace Vanio\DoctrineDomainEvents;

use Doctrine\Common\EventArgs;

class DomainEvent extends EventArgs
{
    /** @var string */
    private $name;

    /** @var mixed[] */
    private $properties;

    /**
     * @param string $name
     * @param mixed[] $properties
     */
    public function __construct(string $name, array $properties = [])
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
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
