<?php

namespace App\Tests\Entity;

use App\Entity\SubmissionMention;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\SubmissionMention
 */
class SubmissionMentionTest extends TestCase {
    public function testGetSubmission(): void {
        $submission = EntityFactory::makeSubmission();
        $mention = new SubmissionMention(EntityFactory::makeUser(), $submission);

        $this->assertSame($submission, $mention->getSubmission());
    }

    public function testGetType(): void {
        $mention = new SubmissionMention(
            EntityFactory::makeUser(),
            EntityFactory::makeSubmission(),
        );

        $this->assertSame('submission_mention', $mention->getType());
    }
}
