<?php

namespace App\Tests\Message;

use App\Message\SendPasswordResetEmail;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Message\SendPasswordResetEmail
 */
class SendPasswordResetEmailTest extends TestCase {
    public function testConstructAndGetEmailAddress(): void {
        $message = new SendPasswordResetEmail('emma@example.com');

        $this->assertSame('emma@example.com', $message->getEmailAddress());
    }
}
