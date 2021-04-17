<?php

namespace App\Tests\Entity;

use App\Entity\ForumBan;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\Entity\ForumBan
 * @group time-sensitive
 */
class ForumBanTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(ForumBan::class);
    }

    public function testConstruction(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $bannedBy = EntityFactory::makeUser();
        $expires = new \DateTime('@'.time().' +600 seconds');

        $ban = new ForumBan($forum, $user, 'some reason', true, $bannedBy, $expires);

        $this->assertInstanceOf(UuidInterface::class, $ban->getId());
        $this->assertSame($forum, $ban->getForum());
        $this->assertSame($user, $ban->getUser());
        $this->assertSame('some reason', $ban->getReason());
        $this->assertSame($bannedBy, $ban->getBannedBy());
        $this->assertSame(time(), $ban->getTimestamp()->getTimestamp());
        $this->assertSame(time() + 600, $ban->getExpires()->getTimestamp());
        $this->assertTrue($ban->isBan());
    }

    public function testCannotConstructExpiringUnban(): void {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unbans cannot have expiry times');

        new ForumBan(
            EntityFactory::makeForum(),
            EntityFactory::makeUser(),
            'asda',
            false,
            EntityFactory::makeUser(),
            new \DateTime()
        );
    }

    public function testExpires(): void {
        $ban = new ForumBan(
            EntityFactory::makeForum(),
            EntityFactory::makeUser(),
            'asda',
            true,
            EntityFactory::makeUser(),
            new \DateTime()
        );

        $this->assertFalse($ban->isExpired());
        sleep(601);
        $this->assertTrue($ban->isExpired());
    }

    public function testIndefiniteBanIsNotExpired(): void {
        $ban = new ForumBan(
            EntityFactory::makeForum(),
            EntityFactory::makeUser(),
            'asda',
            true,
            EntityFactory::makeUser()
        );

        $this->assertFalse($ban->isExpired());
    }
}
