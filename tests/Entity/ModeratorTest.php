<?php

namespace App\Tests\Entity;

use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\Moderator
 */
class ModeratorTest extends TestCase {
    private function moderator(Forum $forum = null, User $user = null): Moderator {
        return new Moderator(
            $forum ?? EntityFactory::makeForum(),
            $user ?? EntityFactory::makeUser(),
        );
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->moderator()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetForum(): void {
        $forum = EntityFactory::makeForum();

        $this->assertSame($forum, $this->moderator($forum)->getForum());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->moderator(null, $user)->getUser());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->moderator()->getTimestamp()->getTimestamp(),
        );
    }

    public function testUserCanRemoveOneselfAsModerator(): void {
        $user = EntityFactory::makeUser();

        $this->assertTrue($this->moderator(null, $user)->userCanRemove($user));
    }

    public function testAdminsCanRemoveModerator(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertTrue($this->moderator()->userCanRemove($user));
    }

    public function testRandomUserCannotRemoveModerators(): void {
        $user = EntityFactory::makeUser();

        $this->assertFalse($this->moderator()->userCanRemove($user));
    }

    public function testNonUserObjectCannotRemoveModerator(): void {
        $this->assertFalse($this->moderator()->userCanRemove(new \Exception()));
    }
}
