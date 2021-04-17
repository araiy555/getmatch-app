<?php

namespace App\Tests\Event;

use App\Event\DeleteSubmission;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\DeleteSubmission
 */
class DeleteSubmissionTest extends TestCase {
    public function testConstructAndGetAttributes(): void {
        $submission = EntityFactory::makeSubmission();
        $event = new DeleteSubmission($submission);

        $this->assertSame($submission, $event->getSubmission());
        $this->assertHasDefaultAttributes($event);
    }

    public function testConstructAsModeratorAndGetAttributes(): void {
        $submission = EntityFactory::makeSubmission();
        $user = EntityFactory::makeUser();
        $originalEvent = new DeleteSubmission($submission);
        $event = $originalEvent->asModerator($user, 'some reason');

        $this->assertHasDefaultAttributes($originalEvent);
        $this->assertNotSame($event, $originalEvent);
        $this->assertSame($submission, $event->getSubmission());
        $this->assertSame('some reason', $event->getReason());
        $this->assertFalse($event->isPermanent());
        $this->assertTrue($event->isModDelete());
    }

    public function testConstructWithPermanenceAndGetAttributes(): void {
        $submission = EntityFactory::makeSubmission();
        $originalEvent = new DeleteSubmission($submission);
        $event = $originalEvent->withPermanence();

        $this->assertHasDefaultAttributes($originalEvent);
        $this->assertNotSame($event, $originalEvent);
        $this->assertSame($submission, $event->getSubmission());
        $this->assertNull($event->getReason());
        $this->assertNull($event->getModerator());
        $this->assertFalse($event->isModDelete());
        $this->assertTrue($event->isPermanent());
    }

    private function assertHasDefaultAttributes(DeleteSubmission $event): void {
        $this->assertNull($event->getModerator());
        $this->assertNull($event->getReason());
        $this->assertFalse($event->isModDelete());
        $this->assertFalse($event->isPermanent());
    }
}
