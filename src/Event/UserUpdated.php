<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserUpdated extends Event {
    /**
     * @var User
     */
    private $before;

    /**
     * @var User
     */
    private $after;

    public function __construct(User $before, User $after) {
        $this->before = $before;
        $this->after = $after;
    }

    public function getBefore(): User {
        return $this->before;
    }

    public function getAfter(): User {
        return $this->after;
    }
}
