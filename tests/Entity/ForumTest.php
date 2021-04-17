<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\BackgroundImageInterface;
use App\Entity\CssTheme;
use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\ForumLogBan;
use App\Entity\ForumLogEntry;
use App\Entity\ForumTag;
use App\Entity\Image;
use App\Entity\Moderator;
use App\Entity\User;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @covers \App\Entity\Forum
 */
class ForumTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(Forum::class);
    }

    private function forum(User $user = null): Forum {
        return new Forum('Dogs', 'Doggies', 'Puppies', 'not cats', $user);
    }

    public function testGetId(): void {
        $this->assertNull($this->forum()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $forum = $this->forum();
        $r = (new \ReflectionClass(Forum::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($forum, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $forum->getId());
    }

    public function testGetName(): void {
        $this->assertSame('Dogs', $this->forum()->getName());
    }

    public function testGetNormalizedName(): void {
        $this->assertSame('dogs', $this->forum()->getNormalizedName());
    }

    public function testSetName(): void {
        $forum = $this->forum();

        $forum->setName('Cats');

        $this->assertSame('Cats', $forum->getName());
        $this->assertSame('cats', $forum->getNormalizedName());
    }

    public function testGetTitle(): void {
        $this->assertSame('Doggies', $this->forum()->getTitle());
    }

    public function testSetTitle(): void {
        $forum = $this->forum();

        $forum->setTitle('Kittens');

        $this->assertSame('Kittens', $forum->getTitle());
    }

    public function testGetDescription(): void {
        $this->assertSame('Puppies', $this->forum()->getDescription());
    }

    public function testSetDescription(): void {
        $forum = $this->forum();

        $forum->setDescription('Not puppies');

        $this->assertSame('Not puppies', $forum->getDescription());
    }

    public function testGetSidebar(): void {
        $this->assertSame('not cats', $this->forum()->getSidebar());
    }

    public function testSetSidebar(): void {
        $forum = $this->forum();

        $forum->setSidebar('not dogs');

        $this->assertSame('not dogs', $forum->getSidebar());
    }

    public function testGetModerators(): void {
        $this->assertSame([], $this->forum()->getModerators());
    }

    public function testGetModeratorsContainsForumCreator(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);

        $this->assertCount(1, $forum->getModerators());
        $this->assertArrayHasKey(0, $forum->getModerators());
        $this->assertSame($user, $forum->getModerators()[0]->getUser());
    }

    public function testGetPaginatedModerators(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);

        $moderators = $forum->getPaginatedModerators(1, 30);

        $this->assertCount(1, $moderators);
        $this->assertContainsOnlyInstancesOf(Moderator::class, $moderators);
        $this->assertSame(1, $moderators->getCurrentPage());
    }

    public function testAddModerator(): void {
        $forum = $this->forum();
        $user = EntityFactory::makeUser();
        $moderator = new Moderator($forum, $user);

        $forum->addModerator($moderator);

        $this->assertCount(1, $forum->getModerators());
        $this->assertArrayHasKey(0, $forum->getModerators());
        $this->assertSame($moderator, $forum->getModerators()[0]);
    }

    public function testCreatorOfForumIsModerator(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);

        $this->assertTrue($forum->userIsModerator($user));
    }

    public function testRandomUserIsNotForumModerator(): void {
        $this->assertFalse(
            $this->forum()->userIsModerator(EntityFactory::makeUser()),
        );
    }

    public function testNonUserObjectIsNotForumModerator(): void {
        $this->assertFalse($this->forum()->userIsModerator(new \Exception()));
    }

    public function testAdminUserIsModerator(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertTrue($this->forum()->userIsModerator($user));
    }

    public function testAdminUserIsNotModeratorWhenCheckingOnlyMods(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertFalse($this->forum()->userIsModerator($user, false));
    }

    public function testForumCreatorIsModeratorWhenCheckingOnlyMods(): void {
        $user = EntityFactory::makeUser();

        $this->assertTrue($this->forum($user)->userIsModerator($user, false));
    }

    public function testIgnoresDuplicateModerators(): void {
        $forum = $this->forum();
        $user = EntityFactory::makeUser();
        $moderator = new Moderator($forum, $user);

        $forum->addModerator($moderator);
        $forum->addModerator($moderator);

        $this->assertCount(1, $forum->getModerators());
    }

    public function testForumModeratorCanDeleteEmptyForum(): void {
        $user = EntityFactory::makeUser();

        $this->assertTrue($this->forum($user)->userCanDelete($user));
    }

    public function testForumModeratorCannotDeleteForumWithSubmissions(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);
        EntityFactory::makeSubmission($forum);

        $this->assertFalse($forum->userCanDelete($user));
    }

    public function testAdminCanDelete(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertTrue($this->forum()->userCanDelete($user));
    }

    public function testRandomUserCannotDelete(): void {
        $this->assertFalse(
            $this->forum()->userCanDelete(EntityFactory::makeUser()),
        );
    }

    public function testNonUserObjectCannotDelete(): void {
        $this->assertFalse($this->forum()->userCanDelete(new \Exception()));
    }

    public function testGetSubmissionCount(): void {
        $this->assertSame(0, $this->forum()->getSubmissionCount());
    }

    public function testAddSubmission(): void {
        $forum = $this->forum();

        $forum->addSubmission(EntityFactory::makeSubmission($forum));

        $this->assertSame(1, $forum->getSubmissionCount());
    }

    public function testCannotAddSubmissionWithOtherForum(): void {
        $submission = EntityFactory::makeSubmission();

        $this->expectException(\InvalidArgumentException::class);

        $this->forum()->addSubmission($submission);
    }

    /**
     * @group time-sensitive
     */
    public function testGetCreated(): void {
        $this->assertSame(time(), $this->forum()->getCreated()->getTimestamp());
    }

    public function testGetSubscriptionCount(): void {
        $this->assertSame(0, $this->forum()->getSubscriptionCount());
    }

    public function testIsSubscribed(): void {
        $user = EntityFactory::makeUser();

        $this->assertFalse($this->forum()->isSubscribed($user));
    }

    public function testForumCreatorIsAddedAsSubscriber(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);

        $this->assertSame(1, $forum->getSubscriptionCount());
        $this->assertTrue($forum->isSubscribed($user));
    }

    public function testSubscribe(): void {
        $forum = $this->forum();
        $user = EntityFactory::makeUser();

        $forum->subscribe($user);

        $this->assertSame(1, $forum->getSubscriptionCount());
        $this->assertTrue($forum->isSubscribed($user));
    }

    public function testUnsubscribe(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum($user);

        $forum->unsubscribe($user);

        $this->assertSame(0, $forum->getSubscriptionCount());
        $this->assertFalse($forum->isSubscribed($user));
    }

    public function testUnsubscribingNonSubscribedUserDoesNothing(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum();

        $forum->unsubscribe($user);

        $this->assertSame(0, $forum->getSubscriptionCount());
        $this->assertFalse($forum->isSubscribed($user));
    }

    public function testUserWithForumBanIsBanned(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->assertTrue($forum->userIsBanned($user));
    }

    public function testAdminsAreNeverBanned(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);
        $forum = $this->forum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->assertFalse($forum->userIsBanned($user));
    }

    public function testUserWithExpiredBanIsNotBanned(): void {
        $user = EntityFactory::makeUser();
        $expires = new \DateTimeImmutable('yesterday');
        $forum = $this->forum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser(), $expires));

        $this->assertFalse($forum->userIsBanned($user));
    }

    public function testUserWithRevertedBanIsNotBanned(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum();
        $forum->addBan(new ForumBan($forum, $user, 'a', false, EntityFactory::makeUser()));

        $this->assertFalse($forum->userIsBanned($user));
    }

    public function testGetPaginatedBansByUser(): void {
        $user = EntityFactory::makeUser();
        $forum = $this->forum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));

        $pager = $forum->getPaginatedBansByUser($user, 1, 30);

        $this->assertCount(1, $pager);
        $this->assertContainsOnlyInstancesOf(ForumBan::class, $pager);
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(30, $pager->getMaxPerPage());
    }

    public function testIsFeatured(): void {
        $this->assertFalse($this->forum()->isFeatured());
    }

    public function testSetFeatured(): void {
        $forum = $this->forum();

        $forum->setFeatured(true);

        $this->assertTrue($forum->isFeatured());
    }

    public function testGetTags(): void {
        $this->assertSame([], $this->forum()->getTags());
    }

    public function testHasTag(): void {
        $tag = new ForumTag('Tag');

        $this->assertFalse($this->forum()->hasTag($tag));
    }

    public function testAddTags(): void {
        $tag1 = new ForumTag('one');
        $tag2 = new ForumTag('two');
        $forum = $this->forum();

        $forum->addTags($tag1, $tag2);

        $this->assertTrue($forum->hasTag($tag1));
        $this->assertTrue($forum->hasTag($tag2));
        $this->assertContains($tag1, $forum->getTags());
        $this->assertContains($tag2, $forum->getTags());
        $this->assertContains($forum, $tag1->getForums());
        $this->assertContains($forum, $tag2->getForums());
    }

    public function testRemoveTags(): void {
        $forum = $this->forum();
        $tag1 = new ForumTag('one');
        $tag2 = new ForumTag('two');
        $forum->addTags($tag1, $tag2);

        $forum->removeTags($tag1, $tag2);

        $this->assertFalse($forum->hasTag($tag1));
        $this->assertFalse($forum->hasTag($tag2));
    }

    public function testGetLightBackgroundImage(): void {
        $this->assertNull($this->forum()->getLightBackgroundImage());
    }

    public function testSetLightBackgroundImage(): void {
        $image = new Image('a.jpg', random_bytes(32), null, null);
        $forum = $this->forum();

        $forum->setLightBackgroundImage($image);

        $this->assertSame($image, $forum->getLightBackgroundImage());
    }

    public function testGetDarkBackgroundImage(): void {
        $this->assertNull($this->forum()->getDarkBackgroundImage());
    }

    public function testSetDarkBackgroundImage(): void {
        $image = new Image('a.jpg', random_bytes(32), null, null);
        $forum = $this->forum();

        $forum->setDarkBackgroundImage($image);

        $this->assertSame($image, $forum->getDarkBackgroundImage());
    }

    public function testGetBackgroundImageMode(): void {
        $this->assertSame(
            BackgroundImageInterface::BACKGROUND_TILE,
            $this->forum()->getBackgroundImageMode(),
        );
    }

    public function testSetBackgroundImageMode(): void {
        $newMode = BackgroundImageInterface::BACKGROUND_CENTER;
        $forum = $this->forum();

        $forum->setBackgroundImageMode($newMode);

        $this->assertSame($newMode, $forum->getBackgroundImageMode());
    }

    public function testGetSuggestedTheme(): void {
        $this->assertNull($this->forum()->getSuggestedTheme());
    }

    public function testSetSuggestedTheme(): void {
        $theme = new CssTheme('a', 'a{}');
        $forum = $this->forum();

        $forum->setSuggestedTheme($theme);

        $this->assertSame($theme, $forum->getSuggestedTheme());
    }

    public function getPaginatedLogEntries(): void {
        $pager = $this->forum()->getPaginatedLogEntries(1, 12);

        $this->assertCount(0, $pager);
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(12, $pager->getMaxPerPage());
    }

    public function testAddLogEntry(): void {
        $forum = $this->forum();

        $forum->addLogEntry(new ForumLogBan(
            new ForumBan($forum, EntityFactory::makeUser(), 'a', true, EntityFactory::makeUser())
        ));

        $pager = $forum->getPaginatedLogEntries(1);
        $this->assertCount(1, $pager);
        $this->assertContainsOnlyInstancesOf(ForumLogEntry::class, $pager);
    }

    public function testOnCreate(): void {
        $this->assertEquals(new Event(), $this->forum()->onCreate());
    }

    public function testOnUpdate(): void {
        $before = $this->forum();
        $after = $this->forum();

        /** @var ForumUpdated $event */
        $event = $after->onUpdate($before);

        $this->assertInstanceOf(ForumUpdated::class, $event);
        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }

    public function testOnDelete(): void {
        $forum = $this->forum();

        $event = $forum->onDelete();

        $this->assertEquals(new ForumDeleted($forum), $event);
    }
}
