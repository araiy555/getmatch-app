<?php

namespace App\Tests\Event;

use App\Event\ForumDeleted;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ForumDeleted
 */
class ForumDeletedTest extends TestCase {
    public function testConstructAndGetForum(): void {
        $forum = EntityFactory::makeForum();
        $event = new ForumDeleted($forum);

        $this->assertSame($forum, $event->getForum());
    }
}
