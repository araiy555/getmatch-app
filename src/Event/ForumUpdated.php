<?php

namespace App\Event;

use App\Entity\Forum;
use Symfony\Contracts\EventDispatcher\Event;

class ForumUpdated extends Event {
    /**
     * @var Forum
     */
    private $before;

    /**
     * @var Forum
     */
    private $after;

    public function __construct(Forum $before, Forum $after) {
        $this->before = $before;
        $this->after = $after;
    }

    public function getBefore(): Forum {
        return $this->before;
    }

    public function getAfter(): Forum {
        return $this->after;
    }
}
