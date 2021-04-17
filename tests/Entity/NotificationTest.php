<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\Entity\Notification
 * @group time-sensitive
 */
class NotificationTest extends TestCase {
    /**
     * @var \App\Entity\User
     */
    private $user;

    /**
     * @var Notification|\PHPUnit\Framework\MockObject\MockObject
     */
    private $notification;

    public static function setUpBeforeClass(): void {
        ClockMock::register(Notification::class);
    }

    protected function setUp(): void {
        $this->user = EntityFactory::makeUser();
        $this->notification = $this->getMockBuilder(Notification::class)
            ->setConstructorArgs([$this->user])
            ->getMockForAbstractClass();
    }

    public function testConstruction(): void {
        $this->assertInstanceOf(FieldsInterface::class, $this->notification->getId()->getFields());
        $this->assertEquals(4, $this->notification->getId()->getFields()->getVersion());
        $this->assertSame($this->user, $this->notification->getUser());
        $this->assertEquals(time(), $this->notification->getTimestamp()->getTimestamp());
    }

    public function testCanDetach() {
        $this->assertEquals(1, $this->user->getNotificationCount());

        $this->notification->detach();
        $this->assertEquals(0, $this->user->getNotificationCount());

        $this->expectException(\BadMethodCallException::class);
        $this->notification->getUser();
    }
}
