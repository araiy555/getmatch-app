<?php

namespace App\Tests\Event;

use App\Event\DeleteComment;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\DeleteComment
 */
class DeleteCommentTest extends TestCase {
    public function testConstructAndGetAttributes(): void {
        $comment = EntityFactory::makeComment();
        $event = new DeleteComment($comment);

        $this->assertSame($comment, $event->getComment());
        $this->assertHasDefaultAttributes($event);
    }

    public function testConstructAsModeratorAndGetAttributes(): void {
        $comment = EntityFactory::makeComment();
        $user = EntityFactory::makeUser();
        $originalEvent = new DeleteComment($comment);
        $event = $originalEvent->asModerator($user, 'some reason', true);

        $this->assertHasDefaultAttributes($originalEvent);
        $this->assertNotSame($event, $originalEvent);
        $this->assertSame($comment, $event->getComment());
        $this->assertSame('some reason', $event->getReason());
        $this->assertFalse($event->isPermanent());
        $this->assertTrue($event->isModDelete());
        $this->assertTrue($event->isRecursive());
    }

    public function testConstructWithPermanenceAndGetAttributes(): void {
        $comment = EntityFactory::makeComment();
        $originalEvent = new DeleteComment($comment);
        $event = $originalEvent->withPermanence();

        $this->assertHasDefaultAttributes($originalEvent);
        $this->assertNotSame($event, $originalEvent);
        $this->assertSame($comment, $event->getComment());
        $this->assertNull($event->getReason());
        $this->assertNull($event->getModerator());
        $this->assertFalse($event->isModDelete());
        $this->assertTrue($event->isPermanent());
        $this->assertFalse($event->isRecursive());
    }

    private function assertHasDefaultAttributes(DeleteComment $event): void {
        $this->assertNull($event->getModerator());
        $this->assertNull($event->getReason());
        $this->assertFalse($event->isModDelete());
        $this->assertFalse($event->isPermanent());
        $this->assertFalse($event->isRecursive());
    }
}
