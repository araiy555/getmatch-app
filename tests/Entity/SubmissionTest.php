<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\Exception\BannedFromForumException;
use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\Image;
use App\Entity\Submission;
use App\Entity\SubmissionVote;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Entity\Vote;
use App\Event\SubmissionCreated;
use App\Event\SubmissionUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @covers \App\Entity\Submission
 */
class SubmissionTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(Submission::class);
    }

    private function submission(Forum $forum = null, User $user = null): Submission {
        return new Submission(
            'title',
            'http://example.com',
            'body',
            $forum ?? EntityFactory::makeForum(),
            $user ?? EntityFactory::makeUser(),
            '::2',
        );
    }

    public function testGetId(): void {
        $this->assertNull($this->submission()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $submission = $this->submission();
        $r = (new \ReflectionClass(Submission::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($submission, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $submission->getId());
    }

    public function testGetTitle(): void {
        $this->assertSame('title', $this->submission()->getTitle());
    }

    public function testSetTitle(): void {
        $submission = $this->submission();

        $submission->setTitle('new title');

        $this->assertSame('new title', $submission->getTitle());
    }

    public function testGetUrl(): void {
        $this->assertSame('http://example.com', $this->submission()->getUrl());
    }

    public function testSetUrl(): void {
        $submission = $this->submission();

        $submission->setUrl('http://www.example.org');

        $this->assertSame('http://www.example.org', $submission->getUrl());
    }

    public function testGetBody(): void {
        $this->assertSame('body', $this->submission()->getBody());
    }

    public function testSetBody(): void {
        $submission = $this->submission();

        $submission->setBody('new body');

        $this->assertSame('new body', $submission->getBody());
    }

    public function testGetMediaType(): void {
        $this->assertSame(Submission::MEDIA_URL, $this->submission()->getMediaType());
    }

    public function testSetMediaType(): void {
        $submission = $this->submission();
        $submission->setUrl(null);

        $submission->setMediaType(Submission::MEDIA_IMAGE);

        $this->assertSame(Submission::MEDIA_IMAGE, $submission->getMediaType());
    }

    public function testCannotSetInvalidMediaType(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->submission()->setMediaType('invalid');
    }

    public function testCannotSetMediaTypeImageOnSubmissionWithUrl(): void {
        $this->expectException(\BadMethodCallException::class);

        $this->submission()->setMediaType(Submission::MEDIA_IMAGE);
    }

    /**
     * @dataProvider provideSubmissionsAndComments
     */
    public function testGetComments(array $comments, Submission $submission): void {
        $this->assertSame($comments, $submission->getComments());
    }

    /**
     * @dataProvider provideSubmissionsAndComments
     */
    public function testGetCommentCount(array $comments, Submission $submission): void {
        $this->assertSame(\count($comments), $submission->getCommentCount());
    }

    public function provideSubmissionsAndComments(): \Generator {
        yield 'no comments' => [[], $this->submission()];

        $submission = $this->submission();
        $comment1 = EntityFactory::makeComment(null, $submission);
        $comment2 = EntityFactory::makeComment(null, $submission);
        yield 'only top-level comments' => [[$comment1, $comment2], $submission];

        $submission = $this->submission();
        $comment1 = EntityFactory::makeComment(null, $submission);
        $comment2 = EntityFactory::makeComment(null, $comment1);
        $comment3 = EntityFactory::makeComment(null, $comment2);
        yield 'nested comments' => [[$comment1, $comment2, $comment3], $submission];
    }

    /**
     * @dataProvider provideSubmissionsAndTopLevelComments
     */
    public function testGetTopLevelComments(array $comments, Submission $submission): void {
        $this->assertSame($comments, $submission->getTopLevelComments());
    }

    public function provideSubmissionsAndTopLevelComments(): \Generator {
        yield 'no comments' => [[], $this->submission()];

        $submission = $this->submission();
        $comment1 = EntityFactory::makeComment(null, $submission);
        $comment2 = EntityFactory::makeComment(null, $submission);
        yield 'only top-level comments' => [[$comment1, $comment2], $submission];

        $submission = $this->submission();
        $comment1 = EntityFactory::makeComment(null, $submission);
        $comment2 = EntityFactory::makeComment(null, $comment1);
        EntityFactory::makeComment(null, $comment2);
        $comment4 = EntityFactory::makeComment(null, $submission);
        yield 'nested comments' => [[$comment1, $comment4], $submission];
    }

    /**
     * @dataProvider provideHasInvisibleComments
     */
    public function testHasVisibleComments(bool $hasVisible, Submission $submisssion): void {
        $this->assertSame($hasVisible, $submisssion->hasVisibleComments());
    }

    public function provideHasInvisibleComments(): \Generator {
        yield 'no comments' => [false, $this->submission()];

        $submission = $this->submission();
        EntityFactory::makeComment(null, $submission);
        yield 'one visible comment' => [true, $submission];

        $submission = $this->submission();
        $comment = EntityFactory::makeComment(null, $submission);
        $comment->trash();
        yield 'one trashed comment' => [false, $submission];

        $submission = $this->submission();
        $comment = EntityFactory::makeComment(null, $submission);
        $comment->softDelete();
        yield 'one soft-deleted comment' => [false, $submission];

        $submission = $this->submission();
        $comment = EntityFactory::makeComment(null, $submission);
        $comment->trash();
        EntityFactory::makeComment(null, $comment);
        yield 'one visible comment as reply to one trashed' => [true, $submission];
    }

    public function testAddComment(): void {
        $submission = $this->submission();

        $submission->addComment(EntityFactory::makeComment(null, $submission));

        $this->assertSame(1, $submission->getCommentCount());
    }

    public function testRemoveComment(): void {
        $submission = $this->submission();
        $comment = EntityFactory::makeComment(null, $submission);
        $submission->addComment($comment);

        $submission->removeComment($comment);

        $this->assertSame(0, $submission->getCommentCount());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->submission()->getTimestamp()->getTimestamp(),
        );
    }

    /**
     * @group time-sensitive
     */
    public function testGetLastActive(): void {
        $this->assertSame(
            time(),
            $this->submission()->getLastActive()->getTimestamp(),
        );
    }

    public function testGetVisibility(): void {
        $this->assertSame(
            Submission::VISIBILITY_VISIBLE,
            $this->submission()->getVisibility(),
        );
    }

    public function testSoftDelete(): void {
        $submission = $this->submission();
        $submission->setImage(new Image('a', random_bytes(32), null, null));
        $submission->setSticky(true);
        $submission->setUserFlag(UserFlags::FLAG_ADMIN);

        $submission->softDelete();

        $this->assertEmpty($submission->getTitle());
        $this->assertNull($submission->getBody());
        $this->assertNull($submission->getImage());
        $this->assertNull($submission->getUrl());
        $this->assertFalse($submission->isSticky());
        $this->assertSame(Submission::MEDIA_URL, $submission->getMediaType());
        $this->assertSame(UserFlags::FLAG_NONE, $submission->getUserFlag());
        $this->assertSame(
            Submission::VISIBILITY_SOFT_DELETED,
            $submission->getVisibility(),
        );
    }

    public function testTrash(): void {
        $submission = $this->submission();
        $submission->setSticky(true);

        $submission->trash();

        $this->assertSame(
            Submission::VISIBILITY_TRASHED,
            $submission->getVisibility(),
        );
    }

    public function testRestore(): void {
        $submission = $this->submission();
        $submission->trash();

        $submission->restore();

        $this->assertSame(
            Submission::VISIBILITY_VISIBLE,
            $submission->getVisibility(),
        );
    }

    public function testCannotRestoreSoftDeleted(): void {
        $submission = $this->submission();
        $submission->softDelete();

        $this->expectException(\DomainException::class);

        $submission->restore();
    }

    public function testGetForum(): void {
        $forum = EntityFactory::makeForum();

        $this->assertSame($forum, $this->submission($forum)->getForum());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->submission(null, $user)->getUser());
    }

    public function testCreateVote(): void {
        $submission = $this->submission();
        $user = EntityFactory::makeUser();

        $vote = $submission->createVote(Votable::VOTE_UP, $user, null);

        $this->assertEquals(
            new SubmissionVote(Votable::VOTE_UP, $user, null, $submission),
            $vote,
        );
    }

    public function testAddVote(): void {
        $submission = $this->submission();
        $user = EntityFactory::makeUser();
        $vote = $submission->createVote(Votable::VOTE_UP, $user, null);

        $submission->addVote($vote);

        $this->assertSame(2, $submission->getNetScore());
    }

    public function testCannotAddNonSubmissionVote(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->submission()->addVote($this->createMock(Vote::class));
    }

    public function testRemoveVote(): void {
        $submission = $this->submission();
        $user = EntityFactory::makeUser();
        $vote = $submission->createVote(Votable::VOTE_UP, $user, null);
        $submission->addVote($vote);

        $submission->removeVote($vote);

        $this->assertSame(1, $submission->getNetScore());
    }

    public function testCannotRemoveNonSubmissionVote(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->submission()->removeVote($this->createMock(Vote::class));
    }

    /**
     * @group time-sensitive
     */
    public function testGetRanking(): void {
        $this->assertSame(time() + 1800, $this->submission()->getRanking());
    }

    public function testGetEditedAt(): void {
        $this->assertNull($this->submission()->getEditedAt());
    }

    public function testSetEditedAt(): void {
        $submission = $this->submission();
        $expected = time();

        $submission->setEditedAt(new \DateTimeImmutable('@'.$expected));

        $this->assertSame($expected, $submission->getEditedAt()->getTimestamp());
    }

    public function testIsModerated(): void {
        $this->assertFalse($this->submission()->isModerated());
    }

    public function testSetModerated(): void {
        $submission = $this->submission();

        $submission->setModerated(true);

        $this->assertTrue($submission->isModerated());
    }

    public function testGetUserFlag(): void {
        $this->assertSame(UserFlags::FLAG_NONE, $this->submission()->getUserFlag());
    }

    /**
     * @dataProvider provideUserFlags
     */
    public function testSetUserFlag(string $userFlag): void {
        $submission = $this->submission();

        $submission->setUserFlag($userFlag);

        $this->assertSame($userFlag, $submission->getUserFlag());
    }

    public function testCannotSetInvalidUserFlag(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->submission()->setUserFlag('invalid');
    }

    public function provideUserFlags(): \Generator {
        foreach (UserFlags::FLAGS as $userFlag) {
            yield $userFlag => [$userFlag];
        }
    }

    public function testIsLocked(): void {
        $this->assertFalse($this->submission()->isLocked());
    }

    public function testSetLocked(): void {
        $submission = $this->submission();

        $submission->setLocked(true);

        $this->assertTrue($submission->isLocked());
    }

    public function testGetNetScore(): void {
        $this->assertSame(1, $this->submission()->getNetScore());
    }

    public function testOnCreate(): void {
        $submission = $this->submission();

        /** @var SubmissionCreated $event */
        $event = $submission->onCreate();

        $this->assertSame($submission, $event->getSubmission());
    }

    public function testOnUpdate(): void {
        $before = $this->submission();
        $after = $this->submission();

        /** @var SubmissionUpdated $event */
        $event = $after->onUpdate($before);

        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }

    public function testOnDelete(): void {
        $this->assertEquals(new Event(), $this->submission()->onDelete());
    }

    public function testCannotCreateSubmissionWithInvalidIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid IP address 'in:va:li:d'");

        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        new Submission('a', null, null, $forum, $user, 'in:va:li:d');
    }

    public function testBannedUserCannotCreateSubmission(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->expectException(BannedFromForumException::class);

        $this->submission($forum, $user);
    }

    public function testBannedUserCannotAddVote(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $submission = $this->submission($forum, $user);
        $forum->addBan(new ForumBan($forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->expectException(BannedFromForumException::class);

        $submission->addVote($submission->createVote(Votable::VOTE_UP, $user, '::1'));
    }
}
