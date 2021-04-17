<?php

namespace App\Tests\Event;

use App\Event\UserUpdated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\UserUpdated
 */
class UserUpdatedTest extends TestCase {
    public function testConstructAndGetUsers(): void {
        $before = EntityFactory::makeUser();
        $after = EntityFactory::makeUser();
        $event = new UserUpdated($before, $after);

        $this->assertSame($before, $event->getBefore());
        $this->assertSame($after, $event->getAfter());
    }
}
