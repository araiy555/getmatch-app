<?php

namespace App\Tests\Event;

use App\Event\SubmissionUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\SubmissionUpdated
 */
class SubmissionUpdatedTest extends TestCase {
    public function testConstructAndGetSubmissions(): void {
        $before = EntityFactory::makeSubmission();
        $after = EntityFactory::makeSubmission();
        $event = new SubmissionUpdated($before, $after);

        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }
}
