<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\Exception\AccountBannedException;
use App\Security\UserChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Security\UserChecker
 */
class UserCheckerTest extends TestCase {
    /**
     * @doesNotPerformAssertions
     */
    public function testNonBannedUserDoesNotCauseExceptionOnAuth(): void {
        /* @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('isBanned')->willReturn(false, false);

        (new UserChecker())->checkPostAuth($user);
    }

    public function testBannedUserCausesExceptionOnPostAuth(): void {
        /* @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('isBanned')->willReturn(true);

        $this->expectException(AccountBannedException::class);

        (new UserChecker())->checkPostAuth($user);
    }
}
