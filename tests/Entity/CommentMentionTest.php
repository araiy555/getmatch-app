<?php

namespace App\Tests\Entity;

use App\Entity\CommentMention;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\CommentMention
 */
class CommentMentionTest extends TestCase {
    public function testGetComment(): void {
        $comment = EntityFactory::makeComment();
        $mention = new CommentMention(EntityFactory::makeUser(), $comment);

        $this->assertSame($comment, $mention->getComment());
    }

    public function testGetType(): void {
        $mention = new CommentMention(
            EntityFactory::makeUser(),
            EntityFactory::makeComment(),
        );

        $this->assertSame('comment_mention', $mention->getType());
    }
}
