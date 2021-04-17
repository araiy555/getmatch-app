<?php

namespace App\Tests\Entity;

use App\Entity\Theme;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\Theme
 */
class ThemeTest extends TestCase {
    private function theme(): Theme {
        return $this->getMockForAbstractClass(Theme::class, ['some name']);
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->theme()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetName(): void {
        $this->assertSame('some name', $this->theme()->getName());
    }

    public function testSetName(): void {
        $theme = $this->theme();

        $theme->setName('other name');

        $this->assertSame('other name', $theme->getName());
    }
}
