<?php

namespace App\Event;

use App\Entity\Comment;
use Symfony\Contracts\EventDispatcher\Event;

class CommentUpdated extends Event {
    /**
     * @var Comment
     */
    private $before;

    /**
     * @var Comment
     */
    private $after;

    public function __construct(Comment $before, Comment $after) {
        $this->before = $before;
        $this->after = $after;
    }

    public function getBefore(): Comment {
        return $this->before;
    }

    public function getAfter(): Comment {
        return $this->after;
    }
}
