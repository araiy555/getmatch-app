<?php

namespace App\Tests\Event;

use App\Event\UserCreated;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\UserCreated
 */
class UserCreatedTest extends TestCase {
    public function testConstructAndGetUser(): void {
        $user = EntityFactory::makeUser();
        $event = new UserCreated($user);

        $this->assertSame($user, $event->getUser());
    }
}
