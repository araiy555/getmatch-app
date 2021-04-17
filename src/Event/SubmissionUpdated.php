<?php

namespace App\Event;

use App\Entity\Submission;
use Symfony\Contracts\EventDispatcher\Event;

class SubmissionUpdated extends Event {
    /**
     * @var Submission
     */
    private $before;

    /**
     * @var Submission
     */
    private $after;

    public function __construct(Submission $before, Submission $after) {
        $this->before = $before;
        $this->after = $after;
    }

    public function getBefore(): Submission {
        return $this->before;
    }

    public function getAfter(): Submission {
        return $this->after;
    }
}
