<?php

namespace App\Tests\Entity;

use App\Entity\CssTheme;
use App\Entity\CssThemeRevision;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\Entity\CssTheme
 */
class CssThemeTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(CssThemeRevision::class);
    }

    /**
     * @group time-sensitive
     */
    public function testThemeConstructorAndGetters(): void {
        $theme = new CssTheme('the-name', '.foo{}');

        $this->assertSame('the-name', $theme->getName());
        $this->assertSame($theme, $theme->getLatestRevision()->getTheme());
        $this->assertSame(time(), $theme->getLatestRevision()->getTimestamp()->getTimestamp());
        $this->assertSame('.foo{}', $theme->getLatestRevision()->getCss());
        $this->assertSame('css', $theme->getType());
    }

    public function testGetLatestRevisionThrowsWhenMissingRevisions(): void {
        $theme = new CssTheme('the-name', '.foo{}');

        $r = (new \ReflectionObject($theme))->getProperty('revisions');
        $r->setAccessible(true);
        $r->setValue($theme, new ArrayCollection());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Theme the-name (id='.$theme->getId()->toString().') does not have any revisions');

        $theme->getLatestRevision();
    }
}
