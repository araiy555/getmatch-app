<?php

namespace App\Tests\Utils;

use App\Utils\Exception\ImageNameGenerationFailedException;
use App\Utils\ImageNameGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * @covers \App\Utils\ImageNameGenerator
 */
class ImageNameGeneratorTest extends TestCase {
    /**
     * @var MimeTypesInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mimeTypes;

    /**
     * @var ImageNameGenerator
     */
    private $generator;

    protected function setUp(): void {
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);
        $this->generator = new ImageNameGenerator($this->mimeTypes);
    }

    public function testCanGuessFilenameOfPngImage(): void {
        $this->mimeTypes
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn('image/png');

        $this->mimeTypes
            ->expects($this->once())
            ->method('getExtensions')
            ->with('image/png')
            ->willReturn(['png', 'nope']);

        $this->assertSame(
            'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9.png',
            $this->generator->generateName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
        );
    }

    public function testThrowsIfMimeTypeCannotBeGuessed(): void {
        $this->expectException(ImageNameGenerationFailedException::class);

        $this->mimeTypes
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn(null);

        $this->mimeTypes
            ->expects($this->never())
            ->method('getExtensions');

        $this->generator->generateName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');
    }

    public function testThrowsIfNoExtensionForMimeType(): void {
        $this->expectException(ImageNameGenerationFailedException::class);

        $this->mimeTypes
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn('image/png');

        $this->mimeTypes
            ->expects($this->once())
            ->method('getExtensions')
            ->with('image/png')
            ->willReturn([]);

        $this->generator->generateName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');
    }
}
