<?php

namespace App\Tests\Event;

use App\Event\SubmissionCreated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\SubmissionCreated
 */
class SubmissionCreatedTest extends TestCase {
    public function testConstructAndGetSubmission(): void {
        $submission = EntityFactory::makeSubmission();
        $event = new SubmissionCreated($submission);

        $this->assertSame($submission, $event->getSubmission());
    }
}
