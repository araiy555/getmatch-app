<?php

namespace App\Tests\Event;

use App\Event\CommentUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CommentUpdated
 */
class CommentUpdatedTest extends TestCase {
    public function testConstructAndGetComments(): void {
        $before = EntityFactory::makeComment();
        $after = EntityFactory::makeComment();
        $event = new CommentUpdated($before, $after);

        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }
}
