<?php

namespace App\Tests\Entity;

use App\Entity\BadPhrase;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\BadPhrase
 */
class BadPhraseTest extends TestCase {
    private function phrase(): BadPhrase {
        return new BadPhrase('phrase', BadPhrase::TYPE_TEXT);
    }

    /**
     * @dataProvider provideGoodRegexes
     */
    public function testCanConstructWithGoodRegex(string $regex): void {
        $this->expectNotToPerformAssertions();

        new BadPhrase($regex, BadPhrase::TYPE_REGEX);
    }

    public function provideGoodRegexes(): iterable {
        yield ['.'];
        yield ['foo(bar)?'];
    }

    /**
     * @dataProvider provideBadRegexes
     */
    public function testCannotConstructWithBadRegex(string $regex): void {
        $this->expectException(\DomainException::class);

        new BadPhrase($regex, BadPhrase::TYPE_REGEX);
    }

    public function provideBadRegexes(): iterable {
        yield [''];
        yield ['foo('];
    }

    public function testCannotConstructWithBadType(): void {
        $this->expectException(\InvalidArgumentException::class);

        new BadPhrase('example.com', 'bad');
    }

    public function testIdIsUuidV4(): void {
        $fields = $this->phrase()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetPhrase(): void {
        $this->assertSame('phrase', $this->phrase()->getPhrase());
    }

    public function testGetPhraseType(): void {
        $this->assertSame(BadPhrase::TYPE_TEXT, $this->phrase()->getPhraseType());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(time(), $this->phrase()->getTimestamp()->getTimestamp());
    }
}
