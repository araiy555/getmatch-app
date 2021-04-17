<?php

namespace App\Tests\Entity;

use App\Entity\CommentNotification;
use App\Entity\CommentVote;
use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\Contracts\Votable;
use App\Entity\CssTheme;
use App\Entity\IpBan;
use App\Entity\Submission;
use App\Entity\SubmissionVote;
use App\Entity\User;
use App\Entity\UserBan;
use App\Event\UserCreated;
use App\Event\UserUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @covers \App\Entity\User
 */
class UserTest extends TestCase {
    private function user(): User {
        return new User('UserName', 'password');
    }

    public function testGetId(): void {
        $this->assertNull($this->user()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $user = $this->user();
        $r = (new \ReflectionClass(User::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($user, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $user->getId());
    }

    public function testGetUsername(): void {
        $this->assertSame('UserName', $this->user()->getUsername());
    }

    public function testGetNormalizedUsername(): void {
        $this->assertSame('username', $this->user()->getNormalizedUsername());
    }

    public function testSetUsername(): void {
        $user = $this->user();

        $user->setUsername('new_Username');

        $this->assertSame('new_Username', $user->getUsername());
        $this->assertSame('new_username', $user->getNormalizedUsername());
    }

    public function testGetPassword(): void {
        $this->assertSame('password', $this->user()->getPassword());
    }

    public function testSetPassword(): void {
        $user = $this->user();

        $user->setPassword('password1');

        $this->assertSame('password1', $user->getPassword());
    }

    /**
     * @dataProvider provideAccountDeleted
     */
    public function testIsAccountDeleted(bool $deleted, User $user): void {
        $this->assertSame($deleted, $user->isAccountDeleted());
    }

    public function provideAccountDeleted(): \Generator {
        yield 'non-deleted account' => [false, $this->user()];

        $user = $this->user();
        $user->setUsername('!deleted123');
        // fixme: account deletion is hideous
        $r = (new \ReflectionClass(User::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($user, 123);
        $r->setAccessible(false);
        yield 'deleted account' => [true, $user];
    }

    public function testGetEmail(): void {
        $this->assertNull($this->user()->getEmail());
    }

    public function testGetNormalizedEmail(): void {
        $this->assertNull($this->user()->getNormalizedEmail());
    }

    public function testSetEmail(): void {
        $user = $this->user();

        $user->setEmail('emma@EXAMPLE.com');

        $this->assertSame('emma@EXAMPLE.com', $user->getEmail());
        $this->assertSame('emma@example.com', $user->getNormalizedEmail());
    }

    /**
     * @group time-sensitive
     */
    public function testGetCreated(): void {
        $this->assertSame(time(), $this->user()->getCreated()->getTimestamp());
    }

    public function testGetLastSeen(): void {
        $this->assertNull($this->user()->getLastSeen());
    }

    public function testUpdateLastSeen(): void {
        $user = $this->user();

        $user->updateLastSeen();

        $this->assertNotNull($user->getLastSeen());
        $this->assertSame(time(), $user->getLastSeen()->getTimestamp());
    }

    public function testGetRegistrationIp(): void {
        $this->assertNull($this->user()->getRegistrationIp());
    }

    /**
     * @dataProvider provideIps
     */
    public function testSetRegistrationIp(string $ip): void {
        $user = $this->user();

        $user->setRegistrationIp($ip);

        $this->assertSame($ip, $user->getRegistrationIp());
    }

    public function provideIps(): \Generator {
        yield 'ipv4' => ['127.0.0.1'];
        yield 'ipv6' => ['::1'];
    }

    public function testCannotSetInvalidRegistrationIp(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->user()->setRegistrationIp('invalid');
    }

    public function testIsAdmin(): void {
        $this->assertFalse($this->user()->isAdmin());
    }

    public function testSetAdmin(): void {
        $user = $this->user();

        $user->setAdmin(true);

        $this->assertTrue($user->isAdmin());
    }

    public function testGetSalt(): void {
        $this->assertNull($this->user()->getSalt());
    }

    public function testEraseCredentials(): void {
        $this->expectNotToPerformAssertions();

        $this->user()->eraseCredentials();
    }

    public function testGetSubscriptionCount(): void {
        $this->assertSame(0, $this->user()->getSubscriptionCount());
    }

    /**
     * @dataProvider provideRolesAndUsers
     */
    public function testGetRoles(array $roles, User $user): void {
        $this->assertEqualsCanonicalizing($roles, $user->getRoles());
    }

    public function provideRolesAndUsers(): \Generator {
        yield 'regular user' => [['ROLE_USER'], $this->user()];

        $user = $this->user();
        $user->setAdmin(true);
        yield 'admin' => [['ROLE_USER', 'ROLE_ADMIN'], $user];

        $user = $this->user();
        $user->setWhitelisted(true);
        yield 'whitelisted user' => [['ROLE_USER', 'ROLE_WHITELISTED'], $user];

        $user = $this->user();
        $user->setAdmin(true);
        $user->setWhitelisted(true);
        yield 'whitelisted and admin user' => [
            ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_WHITELISTED'],
            $user,
        ];
    }

    public function testGetSubmissionCount(): void {
        $this->assertSame(0, $this->user()->getSubmissionCount());
    }

    public function testAddSubmission(): void {
        $user = $this->user();

        $user->addSubmission(EntityFactory::makeSubmission(null, $user));

        $this->assertSame(1, $user->getSubmissionCount());
    }

    public function testCannotAddSubmissionBelongingToOtherUser(): void {
        $user = $this->user();

        $this->expectException(\DomainException::class);

        $user->addSubmission(EntityFactory::makeSubmission());
    }

    public function testGetSubmissionVoteCount(): void {
        $this->assertSame(0, $this->user()->getSubmissionVoteCount());
    }

    public function testAddSubmissionVote(): void {
        $user = $this->user();

        $user->addSubmissionVote(
            new SubmissionVote(
                Votable::VOTE_UP,
                $user,
                null,
                EntityFactory::makeSubmission(),
            ),
        );

        $this->assertSame(1, $user->getSubmissionVoteCount());
    }

    public function testCannotAddVoteBelongingToAnotherUser(): void {
        $user = $this->user();

        $this->expectException(\DomainException::class);

        $user->addSubmissionVote(
            new SubmissionVote(
                Votable::VOTE_UP,
                EntityFactory::makeUser(),
                null,
                EntityFactory::makeSubmission(),
            ),
        );
    }

    public function testGetCommentCount(): void {
        $this->assertSame(0, $this->user()->getCommentCount());
    }

    public function testAddComment(): void {
        $user = $this->user();

        $user->addComment(EntityFactory::makeComment($user));

        $this->assertSame(1, $user->getCommentCount());
    }

    public function testCannotAddCommentBelongingToAnotherUser(): void {
        $this->expectException(\DomainException::class);

        $this->user()->addComment(EntityFactory::makeComment());
    }

    public function testGetCommentVoteCount(): void {
        $this->assertSame(0, $this->user()->getCommentVoteCount());
    }

    public function testAddCommentVote(): void {
        $user = $this->user();

        $user->addCommentVote(
            new CommentVote(
                Votable::VOTE_UP,
                $user,
                null,
                EntityFactory::makeComment(),
            ),
        );

        $this->assertSame(1, $user->getCommentVoteCount());
    }

    public function testCannotAddCommentVoteBelongingToAnotherUser(): void {
        $this->expectException(\DomainException::class);

        $this->user()->addCommentVote(
            new CommentVote(
                Votable::VOTE_DOWN,
                $this->user(),
                null,
                EntityFactory::makeComment(),
            ),
        );
    }

    /**
     * @dataProvider provideUsersAndBans
     */
    public function testGetActiveBan(?UserBan $ban, User $user): void {
        $this->assertSame($ban, $user->getActiveBan());
    }

    public function provideUsersAndBans(): \Generator {
        yield 'no bans for user' => [null, $this->user()];

        $user = $this->user();
        $user->addBan(new UserBan(
            $user,
            'a',
            true,
            $this->user(),
            new \DateTimeImmutable('yesterday'),
        ));
        yield 'expired ban for user' => [null, $user];

        $user = $this->user();
        $user->addBan(new UserBan(
            $user,
            'a',
            false,
            $this->user(),
        ));
        yield 'retracted ban for user' => [null, $user];

        $user = $this->user();
        $ban = new UserBan(
            $user,
            'a',
            true,
            $this->user(),
        );
        $user->addBan($ban);
        yield 'active ban for user' => [$ban, $user];
    }

    public function testIsBanned(): void {
        $this->assertFalse($this->user()->isBanned());
    }

    public function testAddBan(): void {
        $user = $this->user();

        $user->addBan(new UserBan($user, 'a', true, $this->user()));

        $this->assertTrue($user->isBanned());
    }

    public function testGetIpBans(): void {
        $this->assertSame([], $this->user()->getIpBans());
    }

    public function testAddIpBan(): void {
        $user = $this->user();
        $ban = new IpBan('123.123.123.123', 'a', $user, $this->user());

        $user->addIpBan($ban);

        $this->assertSame([$ban], $user->getIpBans());
    }

    public function testCannotAddIpBanMeantForAnotherUser(): void {
        $this->expectException(\DomainException::class);

        $this->user()->addIpBan(
            new IpBan('::1', 'a', $this->user(), $this->user()),
        );
    }

    public function testGetPaginatedHiddenForums(): void {
        $user = $this->user();

        $pager = $user->getPaginatedHiddenForums(1);

        $this->assertCount(0, $pager);
        $this->assertSame(1, $pager->getCurrentPage());
    }

    public function testIsHidingForum(): void {
        $this->assertFalse(
            $this->user()->isHidingForum(EntityFactory::makeForum()),
        );
    }

    public function testHideForum(): void {
        $forum = EntityFactory::makeForum();
        $user = $this->user();

        $user->hideForum($forum);

        $this->assertTrue($user->isHidingForum($forum));
        $this->assertCount(1, $user->getPaginatedHiddenForums(1));
    }

    public function testUnhideForum(): void {
        $forum = EntityFactory::makeForum();
        $user = $this->user();
        $user->hideForum($forum);

        $user->unhideForum($forum);

        $this->assertFalse($user->isHidingForum($forum));
        $this->assertCount(0, $user->getPaginatedHiddenForums(1));
    }

    public function testGetLocale(): void {
        $this->assertSame('en', $this->user()->getLocale());
    }

    public function testSetLocale(): void {
        $user = $this->user();

        $user->setLocale('nb');

        $this->assertSame('nb', $user->getLocale());
    }

    public function testGetTimezone(): void {
        $this->assertEquals(
            new \DateTimeZone(date_default_timezone_get()),
            $this->user()->getTimezone(),
        );
    }

    public function testSetTimezone(): void {
        $user = $this->user();

        $user->setTimezone(new \DateTimeZone('Europe/Oslo'));

        $this->assertEquals(
            new \DateTimeZone('Europe/Oslo'),
            $user->getTimezone(),
        );
    }

    public function testGetNotificationCount(): void {
        $this->assertSame(0, $this->user()->getNotificationCount());
    }

    public function testSendNotification(): void {
        $user = $this->user();
        $notification = new CommentNotification($user, EntityFactory::makeComment());

        $user->sendNotification($notification);

        $this->assertSame(1, $user->getNotificationCount());
    }

    public function testGetNotificationsById(): void {
        $user = $this->user();
        $notification = new CommentNotification($user, EntityFactory::makeComment());
        $user->sendNotification($notification);

        $notifications = $user->getNotificationsById([$notification->getId()]);

        $this->assertSame([$notification], $notifications);
    }

    public function testClearNotification(): void {
        $user = $this->user();
        $notification = new CommentNotification($user, EntityFactory::makeComment());
        $user->sendNotification($notification);

        $user->clearNotification($notification);

        $this->assertSame(0, $user->getNotificationCount());
    }

    public function testNotificationIsDetachedAfterClear(): void {
        $user = $this->user();
        $notification = new CommentNotification($user, EntityFactory::makeComment());
        $user->sendNotification($notification);
        $user->clearNotification($notification);

        $this->expectException(\BadMethodCallException::class);

        $notification->getUser();
    }

    public function testGetPaginatedNotifications(): void {
        $user = $this->user();
        $notification = new CommentNotification($user, EntityFactory::makeComment());
        $user->sendNotification($notification);

        $pager = $user->getPaginatedNotifications(1, 24);

        $this->assertCount(1, $pager);
        $this->assertContains($notification, $pager);
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(24, $pager->getMaxPerPage());
    }

    public function testGetNightMode(): void {
        $this->assertSame(User::NIGHT_MODE_AUTO, $this->user()->getNightMode());
    }

    /**
     * @dataProvider provideNightModes
     */
    public function testSetNightMode(string $nightMode): void {
        $user = $this->user();

        $user->setNightMode($nightMode);

        $this->assertSame($nightMode, $user->getNightMode());
    }

    public function provideNightModes(): \Generator {
        yield [User::NIGHT_MODE_AUTO];
        yield [User::NIGHT_MODE_DARK];
        yield [User::NIGHT_MODE_LIGHT];
    }

    public function testCannotSetInvalidNightMode(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->user()->setNightMode('invalid');
    }

    public function testIsShowCustomStylesheets(): void {
        $this->assertTrue($this->user()->isShowCustomStylesheets());
    }

    public function testSetShowCustomStylesheets(): void {
        $user = $this->user();

        $user->setShowCustomStylesheets(false);

        $this->assertFalse($user->isShowCustomStylesheets());
    }

    public function testIsWhitelisted(): void {
        $this->assertFalse($this->user()->isWhitelisted());
    }

    public function testSetWhitelisted(): void {
        $user = $this->user();

        $user->setWhitelisted(true);

        $this->assertTrue($user->isWhitelisted());
    }

    /**
     * @dataProvider provideWhitelistedOrAdminParams
     */
    public function testIsWhitelistedOrAdmin(
        bool $whitelistedOrAdmin,
        User $user
    ): void {
        $this->assertSame($whitelistedOrAdmin, $user->isWhitelistedOrAdmin());
    }

    public function provideWhitelistedOrAdminParams(): \Generator {
        yield 'non-admin, non-whitelisted user' => [false, $this->user()];

        $user = $this->user();
        $user->setWhitelisted(true);
        yield 'whitelisted user' => [true, $user];

        $user = $this->user();
        $user->setAdmin(true);
        yield 'admin user' => [true, $user];

        $user = $this->user();
        $user->setAdmin(true);
        $user->setWhitelisted(true);
        yield 'admin, whitelisted user' => [true, $user];
    }

    public function testGetPreferredTheme(): void {
        $this->assertNull($this->user()->getPreferredTheme());
    }

    public function testSetPreferredTheme(): void {
        $user = $this->user();
        $theme = new CssTheme('a', 'a{}');

        $user->setPreferredTheme($theme);

        $this->assertSame($theme, $user->getPreferredTheme());
    }

    public function testIsBlocked(): void {
        $this->assertFalse($this->user()->isBlocking($this->user()));
    }

    public function testGetPaginatedBlocks(): void {
        $pager = $this->user()->getPaginatedBlocks(1, 26);

        $this->assertCount(0, $pager);
        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(26, $pager->getMaxPerPage());
    }

    public function testBlock(): void {
        $blocked = $this->user();
        $user = $this->user();

        $user->block($blocked);

        $this->assertCount(1, $user->getPaginatedBlocks(1));
        $this->assertTrue($user->isBlocking($blocked));
    }

    public function testCannotBlockSelf(): void {
        $user = $this->user();

        $this->expectException(\DomainException::class);

        $user->block($user);
    }

    public function testUnblock(): void {
        $blocked = $this->user();
        $user = $this->user();
        $user->block($blocked);

        $user->unblock($blocked);

        $this->assertFalse($user->isBlocking($user));
        $this->assertCount(0, $user->getPaginatedBlocks(1));
    }

    public function testGetFrontPage(): void {
        $this->assertSame(
            Submission::FRONT_SUBSCRIBED,
            $this->user()->getFrontPage(),
        );
    }

    /**
     * @dataProvider provideFrontPages
     */
    public function testSetFrontPage(string $frontPage): void {
        $user = $this->user();

        $user->setFrontPage($frontPage);

        $this->assertSame($frontPage, $user->getFrontPage());
    }

    public function provideFrontPages(): \Generator {
        foreach (Submission::FRONT_PAGE_OPTIONS as $frontPage) {
            yield $frontPage => [$frontPage];
        }
    }

    public function testCannotSetInvalidFrontPage(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->user()->setFrontPage('invalid');
    }

    public function testGetFrontPageSortMode(): void {
        $this->assertSame(
            Submission::SORT_HOT,
            $this->user()->getFrontPageSortMode(),
        );
    }

    /**
     * @dataProvider provideSortModes
     */
    public function testSetFrontPageSortMode(string $sortMode): void {
        $user = $this->user();

        $user->setFrontPageSortMode($sortMode);

        $this->assertSame($sortMode, $user->getFrontPageSortMode());
    }

    public function provideSortModes(): \Generator {
        foreach (Submission::SORT_OPTIONS as $sortMode) {
            yield $sortMode => [$sortMode];
        }
    }

    public function testCannotSetInvalidFrontPageSortMode(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->user()->setFrontPageSortMode('invalid');
    }

    public function testOpenExternalLinksInNewTab(): void {
        $this->assertFalse($this->user()->openExternalLinksInNewTab());
    }

    public function testSetOpenExternalLinksInNewTab(): void {
        $user = $this->user();

        $user->setOpenExternalLinksInNewTab(true);

        $this->assertTrue($user->openExternalLinksInNewTab());
    }

    public function testGetBiography(): void {
        $this->assertNull($this->user()->getBiography());
    }

    public function testSetBiography(): void {
        $user = $this->user();

        $user->setBiography('hey');

        $this->assertSame('hey', $user->getBiography());
    }

    public function testAutoFetchSubmissionTitles(): void {
        $this->assertTrue($this->user()->autoFetchSubmissionTitles());
    }

    public function testSetAutoFetchSubmissionTitles(): void {
        $user = $this->user();

        $user->setAutoFetchSubmissionTitles(false);

        $this->assertFalse($user->autoFetchSubmissionTitles());
    }

    public function testEnabledPostPreviews(): void {
        $this->assertTrue($this->user()->enablePostPreviews());
    }

    public function testSetEnablePostPreviews(): void {
        $user = $this->user();

        $user->setEnablePostPreviews(false);

        $this->assertFalse($user->enablePostPreviews());
    }

    public function testShowThumbnails(): void {
        $this->assertTrue($this->user()->showThumbnails());
    }

    public function testSetShowThumbnails(): void {
        $user = $this->user();

        $user->setShowThumbnails(false);

        $this->assertFalse($user->showThumbnails());
    }

    public function testAllowPrivateMessages(): void {
        $this->assertTrue($this->user()->allowPrivateMessages());
    }

    public function testSetAllowPrivateMessages(): void {
        $user = $this->user();

        $user->setAllowPrivateMessages(false);

        $this->assertFalse($user->allowPrivateMessages());
    }

    public function testGetNotifyOnReply(): void {
        $this->assertTrue($this->user()->getNotifyOnReply());
    }

    public function testSetNotifyOnReply(): void {
        $user = $this->user();

        $user->setNotifyOnReply(false);

        $this->assertFalse($user->getNotifyOnReply());
    }

    public function testGetNotifyOnMentions(): void {
        $this->assertTrue($this->user()->getNotifyOnMentions());
    }

    public function testSetNotifyOnMentions(): void {
        $user = $this->user();

        $user->setNotifyOnMentions(false);

        $this->assertFalse($user->getNotifyOnMentions());
    }

    public function testGetPreferredFonts(): void {
        $this->assertNull($this->user()->getPreferredFonts());
    }

    public function testSetPreferredFonts(): void {
        $user = $this->user();

        $user->setPreferredFonts('Helvetica, Arial');

        $this->assertSame('Helvetica, Arial', $user->getPreferredFonts());
    }

    public function testIsPoppersEnabled(): void {
        $this->assertTrue($this->user()->isPoppersEnabled());
    }

    public function testSetPoppersEnabled(): void {
        $user = $this->user();

        $user->setPoppersEnabled(false);

        $this->assertFalse($user->isPoppersEnabled());
    }

    public function testIsFullWidthDisplayEnabled(): void {
        $this->assertFalse($this->user()->isFullWidthDisplayEnabled());
    }

    public function testSetFullWidthDisplayEnabled(): void {
        $user = $this->user();

        $user->setFullWidthDisplayEnabled(true);

        $this->assertTrue($user->isFullWidthDisplayEnabled());
    }

    public function getSubmissionLinkDestination(): void {
        $this->assertSame(
            SubmissionLinkDestination::URL,
            $this->user()->getSubmissionLinkDestination(),
        );
    }

    /**
     * @dataProvider provideSubmissionLinkDestinations
     */
    public function testSetSubmissionLinkDestination(string $destination): void {
        $user = $this->user();

        $user->setSubmissionLinkDestination($destination);

        $this->assertSame($destination, $user->getSubmissionLinkDestination());
    }

    public function provideSubmissionLinkDestinations(): \Generator {
        foreach (SubmissionLinkDestination::OPTIONS as $destination) {
            yield [$destination];
        }
    }

    public function testCannotSetInvalidSubmissionLinkDestination(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->user()->setSubmissionLinkDestination('invalid');
    }

    public function testOnCreate(): void {
        $user = $this->user();

        /** @var UserCreated $event */
        $event = $user->onCreate();

        $this->assertInstanceOf(UserCreated::class, $event);
        $this->assertSame($user, $event->getUser());
    }

    public function testOnUpdate(): void {
        $before = $this->user();
        $after = $this->user();

        /** @var UserUpdated $event */
        $event = $after->onUpdate($before);

        $this->assertInstanceOf(UserUpdated::class, $event);
        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }

    public function testOnDelete(): void {
        $this->assertEquals(new Event(), $this->user()->onDelete());
    }

    /**
     * @dataProvider provideNormalizedUsernames
     *
     * @param string $expected
     * @param string $input
     */
    public function testCanNormalizeUsername($expected, $input): void {
        $this->assertSame($expected, User::normalizeUsername($input));
    }

    public function provideNormalizedUsernames(): iterable {
        yield ['emma', 'Emma'];
        yield ['zach', 'zaCH'];
    }

    /**
     * @dataProvider provideNormalizedEmails
     */
    public function testCanNormalizeEmail(string $expected, string $input): void {
        $this->assertSame($expected, User::normalizeEmail($input));
    }

    public function provideNormalizedEmails(): iterable {
        yield ['pzm87i6bhxs2vzgm@gmail.com', 'PzM87.I6bhx.S2vzGm@gmail.com'];
        yield ['ays1hbjbpluzdivl@gmail.com', 'AyS1hBjbPLuZDiVl@googlemail.com'];
        yield ['pcpanmvb@gmail.com', 'pCPaNmvB+roHYEByv@gmail.com'];
        yield ['ag9kcmxicbmkec2tldicghc@gmail.com', 'aG9KC.mxIcBMk.ec2tldiCghc+SSOkIach3@gooGLEMail.com'];
        yield ['pCPaNmvBroHYEByv@example.com', 'pCPaNmvBroHYEByv@ExaMPle.CoM'];
    }

    /**
     * @dataProvider provideInvalidEmails
     */
    public function testNormalizeFailsOnInvalidEmailAddress(string $input): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');

        User::normalizeEmail($input);
    }

    public function provideInvalidEmails(): iterable {
        yield ['gasg7a8.'];
        yield ['foo@examplenet@example.net'];
    }
}
