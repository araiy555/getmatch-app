<?php

namespace App\Tests\Utils;

use App\Utils\LanguageDetector;
use LanguageDetection\Language;
use LanguageDetection\LanguageResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\LanguageDetector
 */
class LanguageDetectorTest extends TestCase {
    /**
     * @var Language|\PHPUnit\Framework\MockObject\MockObject
     */
    private $language;

    /**
     * @var LanguageDetector
     */
    private $detector;

    protected function setUp(): void {
        $this->language = $this->createMock(Language::class);
        $this->detector = new LanguageDetector($this->language);
    }

    public function testBestLanguageIsChosen(): void {
        $this->language
            ->expects($this->once())
            ->method('detect')
            ->with('some input')
            ->willReturn(new LanguageResult([
                'en' => 0.69,
                'nb' => 0.420,
            ]));

        $this->assertSame('en', $this->detector->detect('some input', $confidence));
        $this->assertSame(0.69, $confidence);
    }

    public function testNoDetectionForEmptyInput(): void {
        $this->language
            ->expects($this->never())
            ->method('detect');

        $this->assertNull($this->detector->detect(''));
    }
}
