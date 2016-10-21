<?php
namespace Vanio\DoctrineDomainEvents\Tests;

use PHPUnit\Framework\TestCase;
use Vanio\DoctrineDomainEvents\DomainEvent;

class DomainEventTest extends TestCase
{
    function test_getting_name()
    {
        $this->assertSame('name', (new DomainEvent('name'))->name());
    }

    function test_getting_properties()
    {
        $domainEvent = new DomainEvent('name', [
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => null,
        ]);
        $this->assertSame('foo', $domainEvent->foo);
        $this->assertSame('bar', $domainEvent->bar);
        $this->assertNull($domainEvent->baz);
    }

    function test_getting_undefined_property_throws_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Property "foo" does not exist on domain event "name".');
        $this->assertSame('foo', (new DomainEvent('name'))->foo);
    }
}
