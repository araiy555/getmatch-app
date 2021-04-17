<?php

namespace App\Tests\DataTransfer;

use App\DataObject\ForumData;
use App\DataObject\ForumTagData;
use App\DataTransfer\ForumManager;
use App\Entity\BundledTheme;
use App\Entity\Forum;
use App\Entity\ForumTag;
use App\Entity\Image;
use App\Repository\ForumTagRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataTransfer\ForumManager
 */
class ForumManagerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var ForumTagRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $forumTagRepository;

    /**
     * @var ForumManager
     */
    private $forumManager;

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->forumTagRepository = $this->getMockBuilder(ForumTagRepository::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->addMethods(['findByNormalizedName'])
            ->getMock();
        $this->forumManager = new ForumManager($this->entityManager, $this->forumTagRepository);
    }

    public function testForumCreation(): void {
        $existingTag = new ForumTag('existing');

        $this->forumTagRepository
            ->expects($this->once())
            ->method('findByNormalizedName')
            ->with(['existing', 'created'])
            ->willReturn([$existingTag]);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Forum::class));

        $data = $this->getData();
        $forum = $this->forumManager->createForum($data, EntityFactory::makeUser());

        $this->assertForumMatchesData($data, $forum);
        $this->assertSame(1, $forum->getSubscriptionCount());
        $this->assertCount(1, $forum->getModerators());
    }

    public function testForumUpdating(): void {
        $forum = EntityFactory::makeForum();
        $forum->addTags(
            $existingTag = new ForumTag('existing'),
            $removedTag = new ForumTag('removed')
        );

        $this->forumTagRepository
            ->expects($this->once())
            ->method('findByNormalizedName')
            ->with(['existing', 'created'])
            ->willReturn([$existingTag]);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($removedTag);

        $data = $this->getData();
        $this->forumManager->updateForum($forum, $data);

        $this->assertForumMatchesData($data, $forum);
    }

    private function getData(): ForumData {
        $data = new ForumData();
        $data->setName('Technology');
        $data->setTitle('The technology forum');
        $data->setDescription('This is the forum about tech');
        $data->setSidebar("# Rules\n\n1. post about technology");
        $data->setFeatured(true);
        $data->setBackgroundImageMode(Forum::BACKGROUND_CENTER);
        $data->setLightBackgroundImage(new Image('a', random_bytes(32), 3, 4));
        $data->setDarkBackgroundImage(new Image('b', random_bytes(32), 5, 6));
        $data->setSuggestedTheme(new BundledTheme('a', 'a'));

        $existingTagData = new ForumTagData();
        $existingTagData->setName('existing');

        $createdTagData = new ForumTagData();
        $createdTagData->setName('created');

        $data->setTags([$existingTagData, $createdTagData]);

        return $data;
    }

    private function assertForumMatchesData(ForumData $data, Forum $forum): void {
        $this->assertSame($data->getName(), $forum->getName());
        $this->assertSame($data->getTitle(), $forum->getTitle());
        $this->assertSame($data->getDescription(), $forum->getDescription());
        $this->assertSame($data->getSidebar(), $forum->getSidebar());
        $this->assertSame($data->isFeatured(), $forum->isFeatured());
        $this->assertSame($data->getBackgroundImageMode(), $forum->getBackgroundImageMode());
        $this->assertSame($data->getLightBackgroundImage(), $forum->getLightBackgroundImage());
        $this->assertSame($data->getDarkBackgroundImage(), $forum->getDarkBackgroundImage());
        $this->assertSame($data->getSuggestedTheme(), $forum->getSuggestedTheme());
        $this->assertCount(2, $forum->getTags());

        $this->assertEqualsCanonicalizing(
            ['created', 'existing'],
            array_map(static function (ForumTag $tag) {
                return $tag->getName();
            }, $forum->getTags())
        );
    }
}
