<?php

namespace App\Event;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DeleteComment extends Event {
    /**
     * @var Comment
     */
    private $comment;

    /**
     * @var User|null
     */
    private $moderator;

    /**
     * @var string|null
     */
    private $reason;

    private $permanent = false;

    private $recursive = false;

    public function __construct(Comment $comment) {
        $this->comment = $comment;
    }

    public function getComment(): Comment {
        return $this->comment;
    }

    public function getModerator(): ?User {
        return $this->moderator;
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function isRecursive(): bool {
        return $this->recursive;
    }

    public function isModDelete(): bool {
        return isset($this->moderator);
    }

    public function asModerator(User $moderator, string $reason, bool $recursive = false): self {
        $self = clone $this;
        $self->moderator = $moderator;
        $self->reason = $reason;
        $self->recursive = $recursive;

        return $self;
    }

    public function isPermanent(): bool {
        return $this->permanent;
    }

    public function withPermanence(): self {
        $self = clone $this;
        $self->permanent = true;

        return $self;
    }
}
