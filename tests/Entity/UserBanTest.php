<?php

namespace App\Tests\Entity;

use App\Entity\UserBan;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

/**
 * @covers \App\Entity\UserBan
 * @group time-sensitive
 */
class UserBanTest extends TestCase {
    public function testConstruction(): void {
        $user = EntityFactory::makeUser();
        $bannedBy = EntityFactory::makeUser();
        $expires = new \DateTime('@'.time().' +600 seconds');

        $ban = new UserBan($user, 'sdfg', true, $bannedBy, $expires);

        $this->assertInstanceOf(UuidInterface::class, $ban->getId());
        $this->assertSame($user, $ban->getUser());
        $this->assertSame($bannedBy, $ban->getBannedBy());
        $this->assertSame('sdfg', $ban->getReason());
        $this->assertSame(time(), $ban->getTimestamp()->getTimestamp());
        $this->assertSame(time() + 600, $ban->getExpires()->getTimestamp());

        $this->assertSame($ban, $user->getActiveBan());
    }

    public function testExpiration(): void {
        $expires = new \DateTime('@'.time().' +600 seconds');
        $ban = new UserBan(EntityFactory::makeUser(), 'a', true, EntityFactory::makeUser(), $expires);

        $this->assertFalse($ban->isExpired());
        sleep(601);
        $this->assertTrue($ban->isExpired());
    }

    public function testIndefiniteBanNeverExpires(): void {
        $ban = new UserBan(EntityFactory::makeUser(), 'a', true, EntityFactory::makeUser());

        $this->assertFalse($ban->isExpired());
    }

    public function testUnbansCannotExpire(): void {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unbans cannot have expiry times');

        new UserBan(EntityFactory::makeUser(), 'a', false, EntityFactory::makeUser(), new \DateTime());
    }
}
