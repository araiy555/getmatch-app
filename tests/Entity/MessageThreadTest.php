<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\MessageThread
 */
class MessageThreadTest extends TestCase {
    private function thread(User $sender = null, User $receiver = null): MessageThread {
        return new MessageThread(
            $sender ?? EntityFactory::makeUser(),
            $receiver ?? EntityFactory::makeUser(),
        );
    }

    public function testGetId(): void {
        $this->assertNull($this->thread()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $thread = $this->thread();
        $r = (new \ReflectionClass(MessageThread::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($thread, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $thread->getId());
    }

    public function testGetParticipants(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);

        $this->assertSame([$sender, $receiver], $thread->getParticipants());
    }

    public function testGetOtherParticipants(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);

        $this->assertSame([$receiver], $thread->getOtherParticipants($sender));
    }

    public function testBothPartiesAreParticipants(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);

        $this->assertTrue($thread->userIsParticipant($sender));
        $this->assertTrue($thread->userIsParticipant($receiver));
    }

    public function testNonParticipantsCannotAccessThread(): void {
        $user = EntityFactory::makeUser();

        $this->assertFalse($this->thread()->userIsParticipant($user));
    }

    public function testGetMessages(): void {
        $this->assertSame([], $this->thread()->getMessages());
    }

    public function testAddMessage(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);
        $message = new Message($thread, $sender, 'wah', null);
        $thread->addMessage($message);

        $this->assertSame([$message], $thread->getMessages());
    }

    public function testCannotAddMessageFromNonParticipant(): void {
        $thread = $this->thread();

        $this->expectException(\DomainException::class);

        $thread->addMessage(
            new Message($thread, EntityFactory::makeUser(), 'a', null),
        );
    }

    public function testRemoveMessage(): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);
        $message = new Message($thread, $sender, 'wah', null);
        $thread->addMessage($message);

        $thread->removeMessage($message);

        $this->assertSame([], $thread->getMessages());
    }

    /**
     * @dataProvider provideTitlesAndBodies
     */
    public function testGetTitleWithMarkdownHeading(string $title, string $body): void {
        $sender = EntityFactory::makeUser();
        $receiver = EntityFactory::makeUser();
        $thread = $this->thread($sender, $receiver);
        $message = new Message($thread, $sender, $body, null);
        $thread->addMessage($message);

        $this->assertSame($title, $thread->getTitle());
    }

    public function provideTitlesAndBodies(): \Generator {
        yield 'short body' => ['short body', 'short body'];
        yield 'long body' => [str_repeat('a', 100).'…', str_repeat('a', 102)];
        yield 'markdown heading' => ['this is the title', <<<EOMARKDOWN
        # this is the title

        wah
        EOMARKDOWN];
    }
}
