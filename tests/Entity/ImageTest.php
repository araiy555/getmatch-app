<?php

namespace App\Tests\Entity;

use App\Entity\Image;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\Image
 */
class ImageTest extends TestCase {
    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $image = new Image('a.png', random_bytes(32), null, null);
        $fields = $image->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testAcceptsFilename(): void {
        $image = new Image('a.png', random_bytes(32), 420, 69);
        $this->assertSame('a.png', $image->getFileName());
        $this->assertSame('a.png', (string) $image);
    }

    public function testAcceptsRawSha256(): void {
        $sha256 = random_bytes(32);
        $image = new Image('a.png', $sha256, null, null);

        $this->assertSame($sha256, hex2bin($image->getSha256()));
    }

    public function testAcceptsHexEncodedSha256(): void {
        $sha256 = bin2hex(random_bytes(32));
        $image = new Image('a.png', $sha256, null, null);

        $this->assertSame($sha256, $image->getSha256());
    }

    /**
     * @dataProvider provideInvalidSha256
     */
    public function testDoesNotAcceptInvalidSha256(string $invalidSha256): void {
        $this->expectException(\InvalidArgumentException::class);

        new Image('a.png', $invalidSha256, null, null);
    }

    public function provideInvalidSha256(): \Generator {
        yield 'raw, too short' => [random_bytes(30)];
        yield 'raw, too long' => [random_bytes(34)];
        yield 'hex, too short' => [bin2hex(random_bytes(30))];
        yield 'hex, too long' => [bin2hex(random_bytes(34))];
        yield 'hex, invalid' => [str_repeat('g', 64)];
    }

    public function testConstructorWithDimensions(): void {
        $image = new Image('a.png', random_bytes(32), 420, 69);
        $this->assertSame(420, $image->getWidth());
        $this->assertSame(69, $image->getHeight());
    }

    public function testConstructorWithoutDimensions(): void {
        $image = new Image('a.png', random_bytes(32), null, null);
        $this->assertNull($image->getWidth());
        $this->assertNull($image->getHeight());
    }

    /**
     * @dataProvider provideInvalidDimensions
     */
    public function testConstructorDoesNotAcceptInvalidDimensions(?int $width, ?int $height): void {
        $this->expectException(\InvalidArgumentException::class);

        new Image('a.png', random_bytes(32), $width, $height);
    }

    /**
     * @dataProvider provideInvalidDimensions
     */
    public function testDimensionSetterDoesNotAcceptInvalidDimensions(?int $width, ?int $height): void {
        $this->expectException(\InvalidArgumentException::class);

        (new Image('a.png', random_bytes(32), null, null))->setDimensions($width, $height);
    }

    public function provideInvalidDimensions(): iterable {
        yield [420, null];
        yield [null, 69];
        yield [-420, null];
        yield [null, -69];
        yield [-420, -69];
    }
}
