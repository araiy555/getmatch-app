<?php

namespace App\Tests\Message;

use App\Message\DeleteImage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Message\DeleteImage
 */
class DeleteImageTest extends TestCase {
    public function testConstructAndGetFileNames(): void {
        $message = new DeleteImage('foo.bar', 'baz.jpeg');

        $this->assertSame(['foo.bar', 'baz.jpeg'], $message->getFileNames());
    }

    public function testConstructWithNoFileNames(): void {
        $message = new DeleteImage();

        $this->assertEmpty($message->getFileNames());
    }
}
