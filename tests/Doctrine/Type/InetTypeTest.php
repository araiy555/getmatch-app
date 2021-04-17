<?php

namespace App\Tests\Doctrine\Type;

use App\Doctrine\Type\InetType;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\Type\InetType
 */
class InetTypeTest extends TestCase {
    /**
     * @var Type
     */
    private $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PostgreSqlPlatform
     */
    private $platform;

    public static function setUpBeforeClass(): void {
        if (!Type::hasType('inet')) {
            Type::addType('inet', InetType::class);
        }
    }

    protected function setUp(): void {
        $this->type = Type::getType('inet');
        $this->platform = $this->createMock(PostgreSqlPlatform::class);
    }

    public function testMetadata(): void {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
        $this->assertSame('inet', $this->type->getName());
        $this->assertSame('INET', $this->type->getSQLDeclaration([], $this->platform));
    }

    /**
     * @dataProvider inetProvider
     */
    public function testCanConvertValueToDatabaseType(?string $expected, ?string $value): void {
        $this->assertSame(
            $expected,
            $this->type->convertToDatabaseValue($value, $this->platform)
        );
    }

    /**
     * @dataProvider provideIpsWithNonIntegerCidr
     */
    public function testThrowsOnNonIntegerCidr(string $ipWithCidr): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CIDR must be integer');

        $this->type->convertToDatabaseValue($ipWithCidr, $this->platform);
    }

    /**
     * @dataProvider provideInvalidIps
     */
    public function testThrowsOnInvalidIp(string $invalidIp): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid IP address");

        $this->type->convertToDatabaseValue($invalidIp, $this->platform);
    }

    /**
     * @dataProvider provideOutOfRangeIpsWithCidr
     */
    public function testThrowsOnInvalidCidrRange(string $ipWithCidr, int $bitLength): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("CIDR must be between 0 and {$bitLength}");

        $this->type->convertToDatabaseValue($ipWithCidr, $this->platform);
    }

    public function testDoesNotWorkWithNonPostgresPlatforms(): void {
        /** @var \PHPUnit\Framework\MockObject\MockObject|MySqlPlatform $platform */
        $platform = $this->createMock(MySqlPlatform::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform must be PostgreSQL');

        $this->type->convertToDatabaseValue('::1', $platform);
    }

    public function inetProvider(): \Generator {
        yield ['::1', '::1'];
        yield ['::1/128', '::1/128'];
        yield ['aaaa::aaaa/128', 'aaaa::aaaa/128'];
//        yield ['aaaa::/16', 'aaaa::aaaa/16'];
        yield ['127.0.0.1/32', '127.0.0.1/32'];
        yield ['127.255.0.0/16', '127.255.0.0/16'];
        yield [null, null];
    }

    public function provideInvalidIps(): \Generator {
        yield ['256.256.256.256'];
        yield ['applejuice'];
        yield ['::Fffff'];
    }

    public function provideIpsWithNonIntegerCidr(): \Generator {
        yield ['127.0.0.1/'];
        yield ['127.0.0.1/a'];
        yield ['127.0.0.1/4.20'];
        yield ['127.0.0.1/4.0'];
    }

    public function provideOutOfRangeIpsWithCidr(): \Generator {
        yield ['::1/129', 128];
        yield ['::1/-1', 128];
        yield ['255.255.255.255/33', 32];
        yield ['255.255.255.255/-1', 32];
    }
}
