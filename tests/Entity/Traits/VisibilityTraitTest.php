<?php

namespace App\Tests\Entity\Traits;

use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Traits\VisibilityTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Traits\VisibilityTrait
 */
class VisibilityTraitTest extends TestCase {
    public function testVisibility(): void {
        /** @var VisibilityTrait $entity */
        $entity = new class() {
            use VisibilityTrait;

            public $visibility;

            public function getVisibility(): string {
                return $this->visibility;
            }
        };

        $entity->visibility = VisibilityInterface::VISIBILITY_VISIBLE;
        $this->assertTrue($entity->isVisible());
        $this->assertFalse($entity->isTrashed());
        $this->assertFalse($entity->isSoftDeleted());

        $entity->visibility = VisibilityInterface::VISIBILITY_TRASHED;
        $this->assertFalse($entity->isVisible());
        $this->assertTrue($entity->isTrashed());
        $this->assertFalse($entity->isSoftDeleted());

        $entity->visibility = VisibilityInterface::VISIBILITY_SOFT_DELETED;
        $this->assertFalse($entity->isVisible());
        $this->assertFalse($entity->isTrashed());
        $this->assertTrue($entity->isSoftDeleted());
    }
}
