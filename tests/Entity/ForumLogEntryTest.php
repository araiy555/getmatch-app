<?php

namespace App\Tests\Entity;

use App\Entity\Forum;
use App\Entity\ForumLogEntry;
use App\Entity\Moderator;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\ForumLogEntry
 */
class ForumLogEntryTest extends TestCase {
    private function logEntry(
        Forum $forum = null,
        User $user = null
    ): ForumLogEntry {
        return $this->getMockForAbstractClass(ForumLogEntry::class, [
            $forum ?? EntityFactory::makeForum(),
            $user ?? EntityFactory::makeUser(),
        ]);
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->logEntry()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetForum(): void {
        $forum = EntityFactory::makeForum();

        $this->assertSame($forum, $this->logEntry($forum)->getForum());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->logEntry(null, $user)->getUser());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testWasAdmin(bool $wasAdmin, Forum $forum, User $user): void {
        $this->assertSame($wasAdmin, $this->logEntry($forum, $user)->wasAdmin());
    }

    public function provideUsers(): \Generator {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addModerator(new Moderator($forum, $user));
        yield 'moderator user' => [false, $forum, $user];

        $user = EntityFactory::makeUser();
        yield 'non-moderator user' => [true, $forum, $user];
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->logEntry()->getTimestamp()->getTimestamp(),
        );
    }
}
