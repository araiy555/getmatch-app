<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Contracts\Votable;
use App\Entity\Exception\BannedFromForumException;
use App\Entity\Exception\SubmissionLockedException;
use App\Entity\ForumBan;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Entity\Vote;
use App\Event\CommentCreated;
use App\Event\CommentUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @covers \App\Entity\Comment
 */
class CommentTest extends TestCase {
    /**
     * @param Submission|Comment $parent
     */
    private function comment(
        User $user = null,
        $parent = null,
        string $ip = null
    ): Comment {
        return new Comment(
            'some comment body',
            $user ?? EntityFactory::makeUser(),
            $parent ?? EntityFactory::makeSubmission(),
            $ip,
        );
    }

    public function testCannotCreateWithInvalidParent(): void {
        $this->expectException(\TypeError::class);

        $this->comment(null, []);
    }

    public function testCannotCreateWithInvalidIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->comment(null, null, '256.256.256.256');
    }

    public function testCannotReplyToLockedSubmission(): void {
        $user = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission();
        $submission->setLocked(true);

        $this->expectException(SubmissionLockedException::class);

        $this->comment($user, $submission);
    }

    public function testCannotReplyToSubmissionInBannedForum(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));
        $submission = EntityFactory::makeSubmission($forum);

        $this->expectException(BannedFromForumException::class);

        $this->comment($user, $submission);
    }

    public function testCannotReplyToCommentInBannedForum(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));
        $submission = EntityFactory::makeSubmission($forum);
        $comment = $this->comment(null, $submission);

        $this->expectException(BannedFromForumException::class);

        $this->comment($user, $comment);
    }

    public function testGetId(): void {
        $this->assertNull($this->comment()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $comment = $this->comment();
        $r = (new \ReflectionClass(Comment::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($comment, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $comment->getId());
    }

    public function testGetBody(): void {
        $this->assertSame('some comment body', $this->comment()->getBody());
    }

    public function testSetBody(): void {
        $comment = $this->comment();

        $comment->setBody('some other body');

        $this->assertSame('some other body', $comment->getBody());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->comment()->getTimestamp()->getTimestamp(),
        );
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->comment($user)->getUser());
    }

    public function testGetSubmissionWhenSubmissionPassedToConstructor(): void {
        $submission = EntityFactory::makeSubmission();
        $comment = $this->comment(null, $submission);

        $this->assertSame($submission, $comment->getSubmission());
    }

    public function testGetSubmissionWhenCommentPassedToConstructor(): void {
        $submission = EntityFactory::makeSubmission();
        $parent = $this->comment(null, $submission);
        $comment = $this->comment(null, $parent);

        $this->assertSame($submission, $comment->getSubmission());
    }

    public function testGetParent(): void {
        $this->assertNull($this->comment()->getParent());
    }

    public function testGetParentWithCommentPassedToConstructor(): void {
        $parent = $this->comment();
        $comment = $this->comment(null, $parent);

        $this->assertSame($parent, $comment->getParent());
    }

    /**
     * @dataProvider provideTopLevelChildren
     */
    public function testGetChildren(array $children, Comment $comment): void {
        $this->assertSame($children, $comment->getChildren());
    }

    /**
     * @dataProvider provideTopLevelChildren
     */
    public function testGetReplyCount(array $children, Comment $comment): void {
        $this->assertSame(\count($children), $comment->getReplyCount());
    }

    public function provideTopLevelChildren(): \Generator {
        yield 'no replies' => [[], $this->comment()];

        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply2 = $this->comment(null, $comment);
        yield 'replies with no nested replies' => [[$reply1, $reply2], $comment];

        $user = EntityFactory::makeUser();
        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply1->addVote(new CommentVote(Votable::VOTE_DOWN, $user, null, $reply1));
        $reply2 = $this->comment(null, $comment);
        $reply2->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $reply2));
        yield 'reverse order replies' => [[$reply2, $reply1], $comment];

        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply1->addVote(new CommentVote(Votable::VOTE_DOWN, $user, null, $reply1));
        $this->comment(null, $reply1);
        $this->comment(null, $reply1);
        $reply2 = $this->comment(null, $comment);
        $reply2->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $reply2));
        $this->comment(null, $reply2);
        yield 'reverse order, nested replies' => [[$reply2, $reply1], $comment];
    }

    /**
     * @dataProvider provideRecursiveChildren
     */
    public function testGetChildrenRecursive(array $expected, Comment $comment): void {
        $children = \iterator_to_array($comment->getChildrenRecursive());

        $this->assertSame($expected, $children);
    }

    public function provideRecursiveChildren(): \Generator {
        yield 'no replies' => [[], $this->comment()];

        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply2 = $this->comment(null, $comment);
        yield 'replies with no nested replies' => [[$reply1, $reply2], $comment];

        $user = EntityFactory::makeUser();
        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply1->addVote(new CommentVote(Votable::VOTE_DOWN, $user, null, $comment));
        $reply2 = $this->comment(null, $comment);
        $reply2->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $comment));
        yield 'reverse order replies' => [[$reply2, $reply1], $comment];

        $comment = $this->comment();
        $reply1 = $this->comment(null, $comment);
        $reply1->addVote(new CommentVote(Votable::VOTE_DOWN, $user, null, $reply1));
        $reply2 = $this->comment(null, $reply1);
        $reply2->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $reply2));
        $reply3 = $this->comment(null, $reply1);
        $reply3->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $reply3));
        $reply4 = $this->comment(null, $comment);
        $reply5 = $this->comment(null, $reply4);
        $reply5->addVote(new CommentVote(Votable::VOTE_DOWN, $user, null, $reply5));
        yield 'reverse order, nested replies' => [
            [$reply4, $reply5, $reply1, $reply2, $reply3],
            $comment,
        ];
    }

    public function testRemoveReply(): void {
        $comment = $this->comment();
        $reply = $this->comment(null, $comment);

        $comment->removeReply($reply);

        $this->assertNotContains($reply, $comment->getChildren());
    }

    public function testAddMention(): void {
        $receiver = EntityFactory::makeUser();

        $this->comment()->addMention($receiver);

        $this->assertSame(1, $receiver->getNotificationCount());
    }

    public function testWillNotMentionIfUserBlocked(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $receiver->block($sender);

        $this->comment($sender)->addMention($receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotMentionIfAccountDeleted(): void {
        $receiver = EntityFactory::makeUser();
        $receiver->setUsername('!deleted123');
        // fixme: account deletion is hideous
        $r = (new \ReflectionClass(User::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($receiver, 123);
        $r->setAccessible(false);

        $this->comment()->addMention($receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotMentionIfReceiverHasMentionsDisabled(): void {
        $receiver = EntityFactory::makeUser();
        $receiver->setNotifyOnMentions(false);

        $this->comment()->addMention($receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotMentionIfReceiverIsSelf(): void {
        $receiver = EntityFactory::makeUser();

        $this->comment($receiver)->addMention($receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotifyOnlyOnceWhenReceivingReplyAndMention(): void {
        $receiver = EntityFactory::makeUser();
        $comment = $this->comment($receiver);

        $this->comment(null, $comment)->addMention($receiver);

        $this->assertSame(1, $receiver->getNotificationCount());
    }

    /**
     * @group time-sensitive
     */
    public function testGetEditedAt(): void {
        $this->assertNull($this->comment()->getEditedAt());
    }

    /**
     * @group time-sensitive
     */
    public function testUpdateEditedAt(): void {
        $comment = $this->comment();
        sleep(100);

        $comment->updateEditedAt();

        $this->assertSame(time(), $comment->getEditedAt()->getTimestamp());
    }

    public function testIsModerated(): void {
        $this->assertFalse($this->comment()->isModerated());
    }

    public function testSetModerated(): void {
        $comment = $this->comment();

        $comment->setModerated(true);

        $this->assertTrue($comment->isModerated());
    }

    public function testGetUserFlag(): void {
        $this->assertSame(
            UserFlags::FLAG_NONE,
            $this->comment()->getUserFlag(),
        );
    }

    public function testSetUserFlag(): void {
        $comment = $this->comment();

        $comment->setUserFlag(UserFlags::FLAG_ADMIN);

        $this->assertSame(UserFlags::FLAG_ADMIN, $comment->getUserFlag());
    }

    public function testCannotSetInvalidUserFlag(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->comment()->setUserFlag('peasant');
    }

    public function testAddVote(): void {
        $user = EntityFactory::makeUser();
        $comment = $this->comment();

        $comment->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $comment));

        $this->assertSame(2, $comment->getNetScore());
    }

    public function testCannotVoteWithNonCommentVotes(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->comment()->addVote($this->createMock(Vote::class));
    }

    public function testCannotAddVoteWhenBannedFromForum(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));
        $submission = EntityFactory::makeSubmission($forum);
        $comment = $this->comment(null, $submission);

        $this->expectException(BannedFromForumException::class);

        $comment->addVote(new CommentVote(Votable::VOTE_UP, $user, null, $comment));
    }

    public function testRemoveVote(): void {
        $comment = $this->comment();
        $vote = $comment->getUserVote($comment->getUser());

        $comment->removeVote($vote);

        $this->assertSame(0, $comment->getNetScore());
    }

    public function testCannotRemoveNonCommentVotes(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->comment()->removeVote($this->createMock(Vote::class));
    }

    public function testGetVisibility(): void {
        $comment = $this->comment();

        $this->assertSame(
            VisibilityInterface::VISIBILITY_VISIBLE,
            $comment->getVisibility(),
        );
    }

    public function testIsThreadVisible(): void {
        $this->assertTrue($this->comment()->isThreadVisible());
    }

    public function testTrashedThreadWithNoVisibleChildrenIsNotVisible(): void {
        $comment = $this->comment();
        $comment->trash();

        $this->assertFalse($comment->isThreadVisible());
    }

    public function testTrashedThreadWithVisibleChildrenIsVisible(): void {
        $comment = $this->comment();
        $comment->trash();
        $this->comment(null, $comment);

        $this->assertTrue($comment->isThreadVisible());
    }

    public function testTrashThreadWithTrashChildrenIsNotVisible(): void {
        $comment = $this->comment();
        $comment->trash();
        $reply = $this->comment(null, $comment);
        $reply->trash();

        $this->assertFalse($comment->isThreadVisible());
    }

    public function testSoftDelete(): void {
        $submission = $this->getMockBuilder(Submission::class)
            ->setConstructorArgs(['t', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null])
            ->onlyMethods(['updateCommentCount', 'updateRanking', 'updateLastActive'])
            ->getMock();
        $comment = $this->comment(null, $submission);
        $comment->setUserFlag(UserFlags::FLAG_MODERATOR);
        $comment->setBody('trash body');
        $submission->expects($this->once())->method('updateCommentCount');
        $submission->expects($this->once())->method('updateRanking');
        $submission->expects($this->once())->method('updateLastActive');

        $comment->softDelete();

        $this->assertSame(
            VisibilityInterface::VISIBILITY_SOFT_DELETED,
            $comment->getVisibility(),
        );
        $this->assertSame('', $comment->getBody());
        $this->assertSame(UserFlags::FLAG_NONE, $comment->getUserFlag());
    }

    public function testTrash(): void {
        $submission = $this->getMockBuilder(Submission::class)
            ->setConstructorArgs(['t', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null])
            ->onlyMethods(['updateCommentCount', 'updateRanking', 'updateLastActive'])
            ->getMock();
        $comment = $this->comment(null, $submission);
        $submission->expects($this->once())->method('updateCommentCount');
        $submission->expects($this->once())->method('updateRanking');
        $submission->expects($this->once())->method('updateLastActive');

        $comment->trash();

        $this->assertSame(
            VisibilityInterface::VISIBILITY_TRASHED,
            $comment->getVisibility(),
        );
    }

    public function testRestore(): void {
        $submission = $this->getMockBuilder(Submission::class)
            ->setConstructorArgs(['t', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null])
            ->onlyMethods(['updateCommentCount', 'updateRanking', 'updateLastActive'])
            ->getMock();
        $comment = $this->comment(null, $submission);
        $comment->trash();
        $submission->expects($this->once())->method('updateCommentCount');
        $submission->expects($this->once())->method('updateRanking');
        $submission->expects($this->once())->method('updateLastActive');

        $comment->restore();

        $this->assertSame(
            VisibilityInterface::VISIBILITY_VISIBLE,
            $comment->getVisibility(),
        );
    }

    /**
     * @dataProvider provideIps
     */
    public function testGetIp(?string $ip): void {
        $this->assertSame($ip, $this->comment(null, null, $ip)->getIp());
    }

    public function provideIps(): \Generator {
        yield 'ipv4' => ['127.0.0.1'];
        yield 'ipv6' => ['::1'];
        yield 'null' => [null];
    }

    public function testGetNetScore(): void {
        $this->assertSame(1, $this->comment()->getNetScore());
    }

    public function testOnCreate(): void {
        $comment = $this->comment();

        /** @var CommentCreated $event */
        $event = $comment->onCreate();

        $this->assertInstanceOf(CommentCreated::class, $event);
        $this->assertSame($comment, $event->getComment());
    }

    public function testOnUpdate(): void {
        $previous = $this->comment();
        $comment = $this->comment();

        /** @var CommentUpdated $event */
        $event = $comment->onUpdate($previous);

        $this->assertInstanceOf(CommentUpdated::class, $event);
        $this->assertSame($previous, $event->getBefore());
        $this->assertSame($comment, $event->getAfter());
    }

    public function testOnDelete(): void {
        $this->assertEquals(new Event(), $this->comment()->onDelete());
    }

    public function testReplyingToSubmissionSendsNotification(): void {
        $receiver = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $receiver);

        $this->comment(null, $submission);

        $this->assertSame(1, $receiver->getNotificationCount());
    }

    public function testReplyingToCommentSendsNotification(): void {
        $receiver = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $receiver);
        $comment = EntityFactory::makeComment(null, $submission);

        $this->comment(null, $comment);

        $this->assertSame(1, $receiver->getNotificationCount());
    }

    public function testWillNotNotifyWhenReplyingToDeletedAccount(): void {
        $receiver = EntityFactory::makeUser();
        $receiver->setUsername('!deleted123');
        // fixme: account deletion is hideous
        $r = (new \ReflectionClass(User::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($receiver, 123);
        $r->setAccessible(false);
        $submission = EntityFactory::makeSubmission(null, $receiver);

        $this->comment(null, $submission);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotNotifyWhenReplyingToOwnSubmission(): void {
        $receiver = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $receiver);

        $this->comment($receiver, $submission);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotNotifyWhenReplyingToOwnComment(): void {
        $receiver = EntityFactory::makeUser();
        $comment = $this->comment($receiver);

        $this->comment($receiver, $comment);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotNotifyIfReceiverHasNotificationsDisabled(): void {
        $receiver = EntityFactory::makeUser();
        $receiver->setNotifyOnReply(false);
        $submission = EntityFactory::makeSubmission(null, $receiver);

        $this->comment(null, $submission);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testWillNotNotifyIfUserBlocked(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $receiver->block($sender);
        $submission = EntityFactory::makeSubmission(null, $receiver);

        $this->comment($sender, $submission);

        $this->assertSame(0, $receiver->getNotificationCount());
    }
}
