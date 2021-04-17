<?php

namespace App\Tests\Event;

use App\Event\CommentCreated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CommentCreated
 */
class CommentCreatedTest extends TestCase {
    public function testConstructAndGetComment(): void {
        $comment = EntityFactory::makeComment();
        $event = new CommentCreated($comment);

        $this->assertSame($comment, $event->getComment());
    }
}
