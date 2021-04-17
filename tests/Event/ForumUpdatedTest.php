<?php

namespace App\Tests\Event;

use App\Event\ForumUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ForumUpdated
 */
class ForumUpdatedTest extends TestCase {
    public function testConstructAndGetForums(): void {
        $before = EntityFactory::makeForum();
        $after = EntityFactory::makeForum();
        $event = new ForumUpdated($before, $after);

        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }
}
