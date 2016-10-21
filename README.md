# Doctrine Domain Events

[![Build Status](https://travis-ci.org/vaniocz/doctrine-domain-events.svg?branch=master)](https://travis-ci.org/vaniocz/doctrine-domain-events)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/doctrine-domain-events/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/doctrine-domain-events?branch=master)
![PHP7](https://img.shields.io/badge/php-7-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/doctrine-domain-events/license)](https://github.com/vaniocz/doctrine-domain-events/blob/master/LICENSE)

Very lightweight extension for Doctrine2 ORM allowing usage of domain events together with an ability to call flush
inside it's listeners. This library is inspired in Beberlei's
[Doctrine and Domain Events article](http://www.whitewashing.de/2013/07/24/doctrine_and_domainevents.html).

# Installation
Installation can be done as usually using composer.
`composer require vanio/doctrine-domain-events`

You also need to register an instance of `Vanio\DoctrineDomainEvents\DoctrineDomainEventDispatcher` inside Doctrine's
event manager.

```php
use Vanio\DoctrineDomainEvents\DoctrineDomainEventDispatcher;

$eventManager->addSubscriber(new DoctrineDomainEventDispatcher);
```

# Usage

## Setting up your entity
First make sure your entity implements `Vanio\DoctrineDomainEvent` interface and uses the
`Vanio\DoctrineDomainEvent\EventProviderTrait`.
```php
use Vanio\DoctrineDomainEvent\EventProvider;
use Vanio\DoctrineDomainEvent\EventProviderTrait;

class MyEntity implements EventProvider
{
    use EventProviderTrait;
}
```

## Raising domain events
To raise a domain event from your entity use the `raise` method. This method expects either an instance of
`Vanio\DoctrineDomainEvent\DomainEvent` or just a string representing the domain event name (and optional array of
properties). In case of passing a string it will be turned into an instance of the `DomainEvent` class. If you prefer to
create your own event classes you can do so by extending the `DomainEvent` class, just don't forget to extend the `name`
method. The event is dispatched at the end of transaction once your entity has been flushed and all the changes are
projected into database so it is possible to both perform database queries over the changes as well as cancel the
transaction. Event names will be used as method names of corresponding listeners because this implementation uses the
built-in Doctrine event manager to keep it simple thus you need to make sure all your **event names are globally
unique**.  

```php
use Vanio\DoctrineDomainEvent\EventProvider;
use Vanio\DoctrineDomainEvent\EventProviderTrait;

class Article implements EventProvider
{
    use EventProviderTrait;
    
    const EVENT_ARTICLE_PUBLISHED = 'onArticlePublish';

    // ...

    public function publish()
    {
        $this->raise(self::EVENT_ARTICLE_PUBLISHED, ['property' => 'value']);
    }
}
```

## Listening to domain events
Listening to domain events is done the same way as listening to standard Doctrine events because it uses the same event
manager. You need to either prepare a listener and register it to appropriate domain event yourself or you can use a
subscriber that know which events it listens to. More information about the event system can be found inside
[Doctrine documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html).
 
### Listening to domain events using a listener
```php
class Listener
{
    public function onArticlePublish(DomainEvent $event)
    {
        // ...     
    }
}

$eventManager->addListener(Article::EVENT_ARTICLE_PUBLISHED, new Listener);
```

### Listening to domain events using a subscriber
```php
use Doctrine\Common\EventSubscriber;

class Subscriber implements EventSubscriber
{
    public function onArticlePublish(DomainEvent $event)
    {
        // ...     
    }
    
    public function getSubscribedEvents(): array
    {
        return [Article::EVENT_ARTICLE_PUBLISHED];
    }
}

$eventManager->addSubscriber(new Subscriber);
```
