<?php
namespace Vanio\DoctrineDomainEvents\Tests\Fixtures;

use Doctrine\Common\EventSubscriber;
use Vanio\DoctrineDomainEvents\DomainEvent;

abstract class ArticleListener implements EventSubscriber
{
    abstract public function onArticlePublish(DomainEvent $event);

    abstract public function onArticleRemove(DomainEvent $event);

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Article::EVENT_ARTICLE_PUBLISHED, Article::EVENT_ARTICLE_REMOVED];
    }
}
