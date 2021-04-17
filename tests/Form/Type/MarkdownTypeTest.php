<?php

namespace App\Tests\Form\Type;

use App\Form\Type\MarkdownType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\MarkdownType
 */
class MarkdownTypeTest extends TypeTestCase {
    public function testSetNullData(): void {
        $form = $this->factory->create(MarkdownType::class, null);
        $form->submit(null);

        $this->assertNull($form->getData());
    }

    public function testWhitespaceStringIsTransformedToNull(): void {
        $form = $this->factory->create(MarkdownType::class);
        $form->submit("\n\r ");

        $this->assertNull($form->getData());
    }

    public function testOnlyRightTrimApplied(): void {
        $form = $this->factory->create(MarkdownType::class);
        $form->submit(' daddy ');

        $this->assertSame(' daddy', $form->getViewData());
    }
}
