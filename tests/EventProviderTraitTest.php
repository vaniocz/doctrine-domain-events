<?php
namespace Vanio\DoctrineDomainEvents\Tests;

use PHPUnit\Framework\TestCase;
use Vanio\DoctrineDomainEvents\DomainEvent;
use Vanio\DoctrineDomainEvents\EventProvider;
use Vanio\DoctrineDomainEvents\EventProviderTrait;

class EventProviderTraitTest extends TestCase
{
    function test_popping_events()
    {
        $eventProvider = new TestEventProvider;
        $this->assertSame([], $eventProvider->popEvents());

        $eventProvider->raise('foo', ['foo' => 'foo']);
        $eventProvider->raise('bar', ['bar' => 'bar']);

        $domainEvents = [new DomainEvent('foo', ['foo' => 'foo']), new DomainEvent('bar', ['bar' => 'bar'])];
        $this->assertEquals($domainEvents, $eventProvider->popEvents());
        $this->assertSame([], $eventProvider->popEvents());
    }

    function test_order_of_delivery_is_recorded_across_event_providers()
    {
        $firstEventProvider = new TestEventProvider;
        $firstEventProvider->raise(new DomainEvent('name'));

        $secondEventProvider = new TestEventProvider;
        $secondEventProvider->raise(new DomainEvent('name'));

        $this->assertGreaterThan(key($firstEventProvider->popEvents()), key($secondEventProvider->popEvents()));
    }

    function test_raising_invalid_event_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid domain event.');
        $eventProvider = new TestEventProvider;
        $eventProvider->raise(new \stdClass);
    }
}

class TestEventProvider implements EventProvider
{
    use EventProviderTrait {
        raise as public;
    }
}
