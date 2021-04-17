<?php

namespace App\Tests\Utils;

use App\Utils\Differ;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;

/**
 * @covers \App\Utils\Differ
 */
class DifferTest extends TestCase {
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DiffOutputBuilderInterface
     */
    private $outputBuilder;

    /**
     * @var Differ
     */
    private $differ;

    protected function setUp(): void {
        $this->outputBuilder = $this->createMock(DiffOutputBuilderInterface::class);
        $this->differ = new Differ($this->outputBuilder);
    }

    public function testDiff(): void {
        $this->outputBuilder
            ->expects($this->once())
            ->method('getDiff')
            ->with($this->isType('array'))
            ->willReturn('some diff');

        $this->assertSame('some diff', $this->differ->diff('old', 'new'));
    }
}
