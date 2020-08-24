<?php
namespace Vanio\DoctrineDomainEvents\Tests\Fixtures;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Vanio\DoctrineDomainEvents\DomainEvent;

class EditArticleOnPublish implements EventSubscriber
{
    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $content;

    public function __construct(EntityManager $entityManager, string $content)
    {
        $this->entityManager = $entityManager;
        $this->content = $content;
    }

    public function onArticlePublish(DomainEvent $event)
    {
        $this->editArticle($event->article);
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Article::EVENT_ARTICLE_PUBLISHED];
    }

    private function editArticle(Article $article)
    {
        $article->edit($this->content);
        $this->entityManager->flush($article);
    }
}
