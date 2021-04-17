<?php

namespace App\Event;

use App\Entity\Submission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DeleteSubmission extends Event {
    /**
     * @var Submission
     */
    private $submission;

    /**
     * @var User|null
     */
    private $moderator;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var bool
     */
    private $permanent = false;

    public function __construct(Submission $submission) {
        $this->submission = $submission;
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }

    public function isModDelete(): bool {
        return isset($this->moderator);
    }

    public function getModerator(): ?User {
        return $this->moderator;
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function asModerator(User $moderator, string $reason): self {
        $self = clone $this;
        $self->moderator = $moderator;
        $self->reason = $reason;

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
