<?php

namespace App\Tests\Message;

use App\Message\DeleteUser;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Message\DeleteUser
 */
class DeleteUserTest extends TestCase {
    public function testConstructWithUserAndGetId(): void {
        $submission = EntityFactory::makeUser();

        $r = (new \ReflectionObject($submission))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($submission, 123);
        $r->setAccessible(false);

        $message = new DeleteUser($submission);

        $this->assertSame(123, $message->getUserId());
    }

    public function testConstructWithUserIdAndGetId(): void {
        $message = new DeleteUser(321);

        $this->assertSame(321, $message->getUserId());
    }

    public function testThrowsWhenConstructorIsGivenUserWithId(): void {
        $this->expectException(\InvalidArgumentException::class);

        new DeleteUser(EntityFactory::makeUser());
    }

    public function testThrowsWhenConstructorIsGivenInvalidParameter(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        new DeleteUser(EntityFactory::makeComment());
    }
}
