<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageNotification;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\MessageNotification
 */
class MessageNotificationTest extends TestCase {
    public function testGetMessage(): void {
        $message = $this->createMock(Message::class);
        $notification = new MessageNotification(EntityFactory::makeUser(), $message);

        $this->assertSame($message, $notification->getMessage());
    }

    public function testGetType(): void {
        $message = $this->createMock(Message::class);
        $notification = new MessageNotification(EntityFactory::makeUser(), $message);

        $this->assertSame('message', $notification->getType());
    }
}
