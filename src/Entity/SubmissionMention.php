<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class SubmissionMention extends Notification {
    /**
     * @ORM\ManyToOne(targetEntity="Submission", inversedBy="mentions")
     *
     * @var Submission
     */
    private $submission;

    public function __construct(User $receiver, Submission $submission) {
        parent::__construct($receiver);

        $this->submission = $submission;
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }

    public function getType(): string {
        return 'submission_mention';
    }
}
