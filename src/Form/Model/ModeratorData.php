<?php

namespace App\Form\Model;

use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\User;
use App\Validator\Unique;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique({"forum", "user"}, entityClass="App\Entity\Moderator",
 *     message="user.already_moderator", errorPath="user")
 */
class ModeratorData {
    /**
     * @var Forum
     */
    private $forum;

    /**
     * @Assert\NotBlank()
     *
     * @var User|null
     */
    private $user;

    public function __construct(Forum $forum) {
        $this->forum = $forum;
    }

    public function toModerator(): Moderator {
        return new Moderator($this->forum, $this->user);
    }

    public function getForum(): Forum {
        return $this->forum;
    }

    public function getUser(): ?User {
        return $this->user;
    }

    public function setUser(?User $user): void {
        $this->user = $user;
    }
}
