<?php

namespace App\Tests\Utils;

use App\Entity\BadPhrase;
use App\Repository\BadPhraseRepository;
use App\Utils\BadPhraseMatcher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\BadPhraseMatcher
 */
class BadPhraseMatcherTest extends TestCase {
    /**
     * @var BadPhraseMatcher
     */
    private $matcher;

    public function setUp(): void {
        $repository = $this->createMock(BadPhraseRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([
                new BadPhrase('tea', BadPhrase::TYPE_TEXT),
                new BadPhrase('coffee', BadPhrase::TYPE_TEXT),
                new BadPhrase('[bs]ad', BadPhrase::TYPE_REGEX),
                new BadPhrase('(?x) should # not break', BadPhrase::TYPE_REGEX),
            ]);

        $this->matcher = new BadPhraseMatcher($repository, null);
    }

    public function testNonBannedPhraseWillNotMatch(): void {
        $this->assertFalse($this->matcher->matches('food'));
    }

    /**
     * @dataProvider provideBannedWords
     */
    public function testBannedWordsWillMatch(string $bannedWord): void {
        $this->assertTrue($this->matcher->matches($bannedWord));
    }

    public function testBannedTextInsideWordWillNotMatch(): void {
        $this->assertFalse($this->matcher->matches('fee'));
    }

    public function testBannedRegexInsideWordWillMatch(): void {
        $this->assertTrue($this->matcher->matches('sadist'));
    }

    public function provideBannedWords(): iterable {
        yield ['tea'];
        yield ['coffee'];
        yield ['bad'];
        yield ['sad'];
        yield ['should'];
    }
}
