<?php

namespace App\Event;

use App\Entity\Submission;
use Symfony\Contracts\EventDispatcher\Event;

class SubmissionCreated extends Event {
    /**
     * @var Submission
     */
    private $submission;

    public function __construct(Submission $submission) {
        $this->submission = $submission;
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }
}
