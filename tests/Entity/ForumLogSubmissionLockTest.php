<?php

namespace App\Tests\Entity;

use App\Entity\ForumLogSubmissionLock;
use App\Entity\Submission;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ForumLogSubmissionLock
 */
class ForumLogSubmissionLockTest extends TestCase {
    private function logEntry(
        Submission $submission = null,
        bool $locked = true
    ): ForumLogSubmissionLock {
        return new ForumLogSubmissionLock(
            $submission ?? EntityFactory::makeSubmission(),
            EntityFactory::makeUser(),
            $locked,
        );
    }

    public function testGetSubmission(): void {
        $submission = EntityFactory::makeSubmission();

        $this->assertSame($submission, $this->logEntry($submission)->getSubmission());
    }

    public function testGetTitle(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->setTitle('the title');

        $this->assertSame('the title', $this->logEntry($submission)->getTitle());
    }

    public function testGetAuthor(): void {
        $author = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $author);

        $this->assertSame($author, $this->logEntry($submission)->getAuthor());
    }

    /**
     * @dataProvider provideLocked
     */
    public function testGetLocked(bool $locked): void {
        $this->assertSame($locked, $this->logEntry(null, $locked)->getLocked());
    }

    public function provideLocked(): \Generator {
        yield 'locked' => [true];
        yield 'not locked' => [false];
    }

    public function testGetAction(): void {
        $this->assertSame('submission_lock', $this->logEntry()->getAction());
    }
}
