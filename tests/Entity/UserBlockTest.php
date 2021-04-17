<?php

namespace App\Tests\Entity;

use App\Entity\UserBlock;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\UserBlock
 */
class UserBlockTest extends TestCase {
    public function testCannotBlockOneself(): void {
        $user = EntityFactory::makeUser();

        $this->expectException(\DomainException::class);

        new UserBlock($user, $user, null);
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $block = new UserBlock(
            EntityFactory::makeUser(),
            EntityFactory::makeUser(),
            null,
        );
        $fields = $block->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetBlockingUser(): void {
        $blocker = EntityFactory::makeUser();
        $block = new UserBlock($blocker, EntityFactory::makeUser(), null);

        $this->assertSame($blocker, $block->getBlocker());
    }

    public function testGetBlockedUser(): void {
        $blocked = EntityFactory::makeUser();
        $block = new UserBlock(EntityFactory::makeUser(), $blocked, null);

        $this->assertSame($blocked, $block->getBlocked());
    }

    public function testCommentIsSameAsPassedToConstructor(): void {
        $block = new UserBlock(
            EntityFactory::makeUser(),
            EntityFactory::makeUser(),
            'this person sucks',
        );

        $this->assertSame('this person sucks', $block->getComment());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestampOfCreation(): void {
        $block = new UserBlock(
            EntityFactory::makeUser(),
            EntityFactory::makeUser(),
            null,
        );

        $this->assertSame(time(), $block->getTimestamp()->getTimestamp());
    }
}
