<?php

namespace App\Tests\Entity;

use App\Entity\CommentNotification;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\CommentNotification
 */
class CommentNotificationTest extends TestCase {
    public function testGetComment(): void {
        $comment = EntityFactory::makeComment();
        $mention = new CommentNotification(EntityFactory::makeUser(), $comment);

        $this->assertSame($comment, $mention->getComment());
    }

    public function testGetType(): void {
        $mention = new CommentNotification(
            EntityFactory::makeUser(),
            EntityFactory::makeComment(),
        );

        $this->assertSame('comment', $mention->getType());
    }
}
