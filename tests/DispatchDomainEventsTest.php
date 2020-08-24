<?php
namespace Vanio\DoctrineDomainEvents\Tests;

use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\TestCase;
use Vanio\DoctrineDomainEvents\DoctrineDomainEventDispatcher;
use Vanio\DoctrineDomainEvents\DomainEvent;
use Vanio\DoctrineDomainEvents\Tests\Fixtures\Article;
use Vanio\DoctrineDomainEvents\Tests\Fixtures\ArticleListener;
use Vanio\DoctrineDomainEvents\Tests\Fixtures\EditArticleOnPublish;

// Older versions of Doctrine\ORM extend legacy class name
class_alias(TestCase::class, 'PHPUnit_Framework_TestCase');

class DispatchDomainEventsTest extends OrmFunctionalTestCase
{
    /** @var mixed[] */
    protected static $_modelSets = [
        'article' => [Article::class],
    ];

    /** @var ArticleListener|\PHPUnit_Framework_MockObject_MockObject */
    private $articleListenerMock;

    protected function setUp()
    {
        $this->useModelSet('article');
        parent::setUp();
        $this->articleListenerMock = $this->getMockForAbstractClass(ArticleListener::class);
        $this->_em->getEventManager()->addEventSubscriber(new DoctrineDomainEventDispatcher);
        $this->_em->getEventManager()->addEventSubscriber($this->articleListenerMock);
    }

    function test_domain_event_is_dispatched_on_post_flush()
    {
        $article = new Article(__FUNCTION__);

        $this->articleListenerMock
            ->expects($this->once())
            ->method(Article::EVENT_ARTICLE_PUBLISHED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_PUBLISHED, ['article' => $article]));

        $article->publish();
        $this->_em->persist($article);
        $this->_em->flush();
    }

    function test_domain_event_is_not_dispatched_without_flushing()
    {
        $this->articleListenerMock
            ->expects($this->never())
            ->method(Article::EVENT_ARTICLE_PUBLISHED);

        $article = new Article(__FUNCTION__);
        $article->publish();
        $this->_em->persist($article);
    }

    function test_domain_events_are_dispatched_on_post_remove()
    {
        $article = new Article(__FUNCTION__);

        $this->articleListenerMock
            ->expects($this->at(0))
            ->method(Article::EVENT_ARTICLE_PUBLISHED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_PUBLISHED, ['article' => $article]));
        $this->articleListenerMock
            ->expects($this->at(1))
            ->method(Article::EVENT_ARTICLE_REMOVED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_REMOVED, ['article' => $article]));

        $article->publish();
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->remove($article);
        $this->_em->flush();
    }

    function test_multiple_domain_events_are_dispatched_on_post_remove()
    {
        $firstArticle = new Article('first');
        $secondArticle = new Article('second');

        $this->articleListenerMock
            ->expects($this->exactly(2))
            ->method(Article::EVENT_ARTICLE_REMOVED);
        $this->articleListenerMock
            ->expects($this->at(0))
            ->method(Article::EVENT_ARTICLE_REMOVED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_REMOVED, ['article' => $firstArticle]));
        $this->articleListenerMock
            ->expects($this->at(1))
            ->method(Article::EVENT_ARTICLE_REMOVED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_REMOVED, ['article' => $secondArticle]));

        $this->_em->persist($firstArticle);
        $this->_em->persist($secondArticle);
        $this->_em->flush();
        $this->_em->remove($firstArticle);
        $this->_em->remove($secondArticle);
        $this->_em->flush();
    }

    function test_domain_events_are_dispatched_in_correct_order()
    {
        $firstArticle = new Article('first');
        $secondArticle = new Article('second');

        $this->articleListenerMock
            ->expects($this->at(0))
            ->method(Article::EVENT_ARTICLE_PUBLISHED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_PUBLISHED, ['article' => $secondArticle]));
        $this->articleListenerMock
            ->expects($this->at(1))
            ->method(Article::EVENT_ARTICLE_REMOVED)
            ->with(new DomainEvent(Article::EVENT_ARTICLE_REMOVED, ['article' => $firstArticle]));

        $this->_em->persist($firstArticle);
        $this->_em->persist($secondArticle);
        $this->_em->flush();
        $secondArticle->publish();
        $this->_em->remove($firstArticle);
        $this->_em->flush();
    }

    function test_flushing_inside_flush()
    {
        $this->_em->getEventManager()->addEventSubscriber(new EditArticleOnPublish($this->_em, 'content'));
        $article = new Article(__FUNCTION__);
        $article->publish();
        $this->_em->persist($article);
        $this->_em->flush();
        $this->assertSame('content', $article->content());
        $this->_em->refresh($article);
        $this->assertSame('content', $article->content());
    }
}
