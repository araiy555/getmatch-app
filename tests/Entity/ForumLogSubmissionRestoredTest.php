<?php

namespace App\Tests\Entity;

use App\Entity\ForumLogSubmissionRestored;
use App\Entity\Submission;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ForumLogSubmissionRestored
 */
class ForumLogSubmissionRestoredTest extends TestCase {
    private function logEntry(Submission $submission = null): ForumLogSubmissionRestored {
        return new ForumLogSubmissionRestored(
            $submission ?? EntityFactory::makeSubmission(),
            EntityFactory::makeUser(),
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

    public function testGetAction(): void {
        $this->assertSame('submission_restored', $this->logEntry()->getAction());
    }
}
