<?php

namespace App\Tests\Asset;

use App\Asset\HashingVersionStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Asset\HashingVersionStrategy
 */
class HashingVersionStrategyTest extends TestCase {
    /**
     * @var HashingVersionStrategy
     */
    private $strategy;

    protected function setUp(): void {
        $this->strategy = new HashingVersionStrategy(__DIR__.'/../Resources');
    }

    public function testGetVersion(): void {
        $this->assertSame(
            '1052d25a298fce69',
            $this->strategy->getVersion('garbage.bin')
        );
    }

    public function testApplyVersion(): void {
        $this->assertSame(
            'garbage.bin?1052d25a298fce69',
            $this->strategy->applyVersion('garbage.bin')
        );
    }

    public function testGetVersionReturnsEmptyStringOnNonExistentFile(): void {
        $this->assertSame('', $this->strategy->getVersion('nonexist.ing'));
    }

    public function testApplyVersionReturnsUnmodifiedPathOnNonExistentFile(): void {
        $this->assertSame('nonexist.ing', $this->strategy->applyVersion('nonexist.ing'));
    }
}
