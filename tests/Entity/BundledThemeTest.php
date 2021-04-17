<?php

namespace App\Tests\Entity;

use App\Entity\BundledTheme;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\BundledTheme
 */
class BundledThemeTest extends TestCase {
    public function testThemeConstructorAndGetters(): void {
        $theme = new BundledTheme('the-name', 'the-key');

        $this->assertSame('the-name', $theme->getName());
        $this->assertSame('the-key', $theme->getConfigKey());
        $this->assertSame('bundled', $theme->getType());
    }
}
