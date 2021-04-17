<?php

namespace App\Message;

use App\Entity\User;

class DeleteUser {
    /**
     * @var int
     */
    private $userId;

    /**
     * @param User|int $user
     */
    public function __construct($user) {
        if ($user instanceof User) {
            if ($user->getId() === null) {
                throw new \InvalidArgumentException('The given user must have an ID');
            }

            $this->userId = $user->getId();
        } elseif (is_scalar($user)) {
            $this->userId = $user;
        } else {
            throw new \TypeError(sprintf(
                '$user must be integer or instance of %s, %s given',
                User::class,
                get_debug_type($user),
            ));
        }
    }

    public function getUserId(): int {
        return $this->userId;
    }
}
