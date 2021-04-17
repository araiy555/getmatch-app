<?php

namespace App\Event;

use App\Entity\Comment;
use Symfony\Contracts\EventDispatcher\Event;

class CommentCreated extends Event {
    /**
     * @var Comment
     */
    private $comment;

    public function __construct(Comment $comment) {
        $this->comment = $comment;
    }

    public function getComment(): Comment {
        return $this->comment;
    }
}
