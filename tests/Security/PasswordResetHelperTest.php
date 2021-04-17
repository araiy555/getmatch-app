<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\PasswordResetHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \App\Security\PasswordResetHelper
 */
class PasswordResetHelperTest extends TestCase {
    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlGenerator;

    public static function setUpBeforeClass(): void {
        ClockMock::register(PasswordResetHelper::class);
    }

    protected function setUp(): void {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    }

    public function testCannotInitializeWithEmptySecret(): void {
        $this->expectException(\InvalidArgumentException::class);
        new PasswordResetHelper($this->urlGenerator, 'no-reply@example.com', '');
    }

    public function testCanResetIfNoReplyAddressIsSet(): void {
        $helper = new PasswordResetHelper($this->urlGenerator, 'no-reply@example.com', 'asasdasd');

        $this->assertTrue($helper->canReset());
    }

    public function testCannotResetIfNoReplyAddressIsNotSet(): void {
        $helper = new PasswordResetHelper($this->urlGenerator, null, 'asasdasd');

        $this->assertFalse($helper->canReset());
    }

    public function testGeneratesUrl(): void {
        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);
        $user
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(69);
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('password');

        $checksum = hash_hmac('sha256', '69~password~'.(time() + 86400), 'secret');

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('password_reset'),
                $this->equalTo([
                    'id' => 69,
                    'expires' => time() + 86400,
                    'checksum' => $checksum,
                ])
            )
            ->willReturn('irrelevant');

        $helper = new PasswordResetHelper($this->urlGenerator, 'no-reply@example.com', 'secret');
        $this->assertSame('irrelevant', $helper->generateResetUrl($user));
    }

    public function testDeniesExpiredLinks(): void {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Link expired');

        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);

        $helper = new PasswordResetHelper($this->urlGenerator, 'no-reply@example.com', 'secret');
        $helper->denyUnlessValidChecksum('irrelevant', $user, time());
    }

    public function testDeniesInvalidChecksums(): void {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Invalid checksum');

        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(69);
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('password');

        $helper = new PasswordResetHelper($this->urlGenerator, 'no-reply@example.com', 'secret');
        $helper->denyUnlessValidChecksum('invalid', $user, time() + 69);
    }
}
