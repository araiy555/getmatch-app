<?php

namespace App\Tests\Utils\Exception;

use App\Utils\Exception\ImageDownloadTooLargeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Exception\ImageDownloadTooLargeException
 */
class ImageDownloadTooLargeExceptionTest extends TestCase {
    public function testExceptionMessage(): void {
        $e = new ImageDownloadTooLargeException(4321, 5432);

        $this->assertStringContainsString('5432/4321', $e->getMessage());
    }
}
