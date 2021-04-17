<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\Message
 */
class MessageTest extends TestCase {
    private function message(
        MessageThread $thread = null,
        User $sender = null,
        User $receiver = null
    ): Message {
        $sender = $sender ?? EntityFactory::makeUser();
        $receiver = $receiver ?? EntityFactory::makeUser();
        $thread = $thread ?? new MessageThread($sender, $receiver);

        return new Message($thread, $thread->getParticipants()[0], 'the body', '::1');
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->message()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetThread(): void {
        $thread = new MessageThread(
            EntityFactory::makeUser(),
            EntityFactory::makeUser(),
        );

        $this->assertSame($thread, $this->message($thread)->getThread());
    }

    public function testGetSender(): void {
        $sender = EntityFactory::makeUser();

        $this->assertSame($sender, $this->message(null, $sender)->getSender());
    }

    public function testGetBody(): void {
        $this->assertSame('the body', $this->message()->getBody());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->message()->getTimestamp()->getTimestamp(),
        );
    }

    public function testGetIp(): void {
        $this->assertSame('::1', $this->message()->getIp());
    }

    public function testNewMessageSendsNotificationToReceiver(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();

        $this->message(null, $sender, $receiver);

        $this->assertSame(1, $receiver->getNotificationCount());
        $this->assertSame(0, $sender->getNotificationCount());
    }

    public function testDoesNotSendNotificationIfSenderBlocked(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $receiver->block($sender);

        $this->message(null, $sender, $receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testDoesNotSendNotificationIfReceiverAccountDeleted(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $receiver->setUsername('!deleted123');
        $r = (new \ReflectionClass(User::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($receiver, 123);
        $r->setAccessible(false);

        $this->message(null, $sender, $receiver);

        $this->assertSame(0, $receiver->getNotificationCount());
    }

    public function testCannotCreateMessageWithInvalidIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);

        new Message(
            $this->createMock(MessageThread::class),
            EntityFactory::makeUser(),
            'a',
            'gggg::'
        );
    }
}
