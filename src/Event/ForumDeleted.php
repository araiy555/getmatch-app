<?php

namespace App\Event;

use App\Entity\Forum;
use Symfony\Contracts\EventDispatcher\Event;

class ForumDeleted extends Event {
    /**
     * @var Forum
     */
    private $forum;

    public function __construct(Forum $forum) {
        $this->forum = $forum;
    }

    public function getForum(): Forum {
        return $this->forum;
    }
}
