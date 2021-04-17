<?php

namespace App\DataObject;

use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

class ModeratorData {
    /**
     * @Groups({"moderator:user-side"})
     *
     * @var Forum
     */
    public $forum;

    /**
     * @Groups({"moderator:forum-side"})
     *
     * @var User
     */
    public $user;

    /**
     * @Groups({"moderator:forum-side", "moderator:user-side"})
     *
     * @var \DateTimeImmutable
     */
    private $since;

    public function __construct(Moderator $moderator = null) {
        if ($moderator) {
            $this->forum = $moderator->getForum();
            $this->user = $moderator->getUser();
            $this->since = $moderator->getTimestamp();
        }
    }

    public function getSince(): \DateTimeImmutable {
        return $this->since;
    }
}
