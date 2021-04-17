<?php

namespace App\Tests\Entity;

use App\Entity\CssTheme;
use App\Entity\CssThemeRevision;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\CssThemeRevision
 */
class CssThemeRevisionTest extends TestCase {
    private function revision(CssTheme $theme = null): CssThemeRevision {
        return ($theme ?? new CssTheme('a', 'a{}'))->getLatestRevision();
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->revision()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetCss(): void {
        $this->assertSame('a{}', $this->revision()->getCss());
    }

    public function testGetTheme(): void {
        $theme = new CssTheme('b', 'b{}');

        $this->assertSame($theme, $this->revision($theme)->getTheme());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->revision()->getTimestamp()->getTimestamp(),
        );
    }
}
