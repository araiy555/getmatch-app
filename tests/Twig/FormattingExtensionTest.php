<?php

namespace App\Tests\Twig;

use App\Twig\FormattingExtension;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\FormattingExtension
 */
class FormattingExtensionTest extends TestCase {
    public function testSearchHighlighting(): void {
        $this->assertSame(
            'foo <mark>bar</mark>',
            FormattingExtension::highlightSearch('foo &lt;b&gt;bar&lt;/b&gt;')
        );
    }
}
