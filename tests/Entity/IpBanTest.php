<?php

namespace App\Tests\Entity;

use App\Entity\IpBan;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\IpBan
 * @group time-sensitive
 */
class IpBanTest extends TestCase {
    private function ipBan(
        string $ip = null,
        User $banned = null,
        User $banningUser = null,
        \DateTimeInterface $expires = null
    ): IpBan {
        return new IpBan(
            $ip ?? '127.0.0.1',
            'reason',
            $banned,
            $banningUser ?? EntityFactory::makeUser(),
            $expires,
        );
    }

    public function testGetId(): void {
        $this->assertNull($this->ipBan()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $ipBan = $this->ipBan();
        $r = (new \ReflectionClass(IpBan::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($ipBan, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $ipBan->getId());
    }

    public function testGetReason(): void {
        $this->assertSame('reason', $this->ipBan()->getReason());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->ipBan(null, $user)->getUser());
    }

    public function testGetBannedBy(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->ipBan(null, null, $user)->getBannedBy());
    }

    /**
     * @dataProvider provideNotRangeIps
     */
    public function testIsNotRangeBan(string $ip): void {
        $this->assertFalse($this->ipBan($ip)->isRangeBan());
    }

    public function provideNotRangeIps(): \Generator {
        yield 'ipv4' => ['192.168.0.0'];
        yield 'ipv4 with cidr' => ['192.168.0.4/32'];
        yield 'ipv6' => ['::1'];
        yield 'ipv6 with cidr' => ['ffff:1124:1241:5125::/128'];
    }

    /**
     * @dataProvider provideRangeIps
     */
    public function testIsRangeBan(string $ip): void {
        $this->assertTrue($this->ipBan($ip)->isRangeBan());
    }

    public function provideRangeIps(): \Generator {
        yield 'ipv4 1' => ['192.168.0.1/24'];
        yield 'ipv4 2' => ['192.168.0.0/31'];
        yield 'ipv6 1' => ['::/127'];
        yield 'ipv6 2' => ['ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff/1'];
    }

    public function testCannotConstructWithInvalidIp(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$ip must be valid IP with optional CIDR range');

        new IpBan('256.256.256.256', 'a', null, EntityFactory::makeUser());
    }

    /**
     * @dataProvider provideInvalidIpsWithMasks
     */
    public function testCannotConstructWithInvalidCidr(string $invalidIp): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->ipBan($invalidIp);
    }

    public function provideInvalidIpsWithMasks(): \Generator {
        yield ['1.1.1.1/33'];
        yield ['1.1.1.1/335782317590127581273589012375890127389012357890123578902357890235789025378901235789012357905789012537890'];
        yield ['2001:4:4:4::4/129'];
        yield ['2001:4:4:4::4/-1'];
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->ipBan()->getTimestamp()->getTimestamp(),
        );
    }

    /**
     * @dataProvider provideExpires
     */
    public function testGetExpires(
        ?\DateTimeInterface $expected,
        ?\DateTimeInterface $expires
    ): void {
        $this->assertEquals(
            $expected,
            $this->ipBan(null, null, null, $expires)->getExpires(),
        );
    }

    public function provideExpires(): \Generator {
        yield [new \DateTimeImmutable('@300'), new \DateTimeImmutable('@300')];
        yield [new \DateTimeImmutable('@300'), new \DateTime('@300')];
        yield [null, null];
    }
}
