<?php

namespace App\Security;

use App\Entity\User;
use App\Security\Exception\AccountBannedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface {
    public function checkPreAuth(UserInterface $user): void {
    }

    public function checkPostAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            throw new AccountBannedException();
        }
    }
}
