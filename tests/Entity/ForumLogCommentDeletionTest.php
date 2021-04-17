<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\ForumLogCommentDeletion;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ForumLogCommentDeletion
 */
class ForumLogCommentDeletionTest extends TestCase {
    private function logEntry(Comment $comment = null): ForumLogCommentDeletion {
        return new ForumLogCommentDeletion(
            $comment ?? EntityFactory::makeComment(),
            EntityFactory::makeUser(),
            'the reason',
        );
    }

    public function testGetAuthor(): void {
        $author = EntityFactory::makeUser();
        $comment = EntityFactory::makeComment($author);

        $this->assertSame($author, $this->logEntry($comment)->getAuthor());
    }

    public function testGetSubmission(): void {
        $submission = EntityFactory::makeSubmission();
        $comment = EntityFactory::makeComment(null, $submission);

        $this->assertSame($submission, $this->logEntry($comment)->getSubmission());
    }

    public function testGetTitle(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->setTitle('the title');
        $comment = EntityFactory::makeComment(null, $submission);

        $this->assertSame('the title', $this->logEntry($comment)->getTitle());
    }

    public function testGetReason(): void {
        $this->assertSame('the reason', $this->logEntry()->getReason());
    }

    public function testGetAction(): void {
        $this->assertSame('comment_deletion', $this->logEntry()->getAction());
    }
}
