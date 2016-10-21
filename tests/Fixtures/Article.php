<?php
namespace Vanio\DoctrineDomainEvents\Tests\Fixtures;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PostRemove;
use Vanio\DoctrineDomainEvents\EventProvider;
use Vanio\DoctrineDomainEvents\EventProviderTrait;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Article implements EventProvider
{
    use EventProviderTrait;

    const EVENT_ARTICLE_PUBLISHED = 'onArticlePublish';
    const EVENT_ARTICLE_REMOVED = 'onArticleRemove';

    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    private $id;

    /**
     * @var string|null
     * @Column(type="string", nullable=true)
     */
    private $content;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function content()
    {
        return $this->content;
    }

    public function edit(string $content)
    {
        $this->content = $content;
    }

    public function publish()
    {
        $this->raise(self::EVENT_ARTICLE_PUBLISHED, ['article' => $this]);
    }

    /**
     * @PostRemove
     */
    public function onPostRemove()
    {
        $this->raise(self::EVENT_ARTICLE_REMOVED, ['article' => $this]);
    }
}
