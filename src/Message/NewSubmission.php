<?php

namespace App\Message;

use App\Entity\Submission;

class NewSubmission {
    /**
     * @var int
     */
    private $submissionId;

    /**
     * @param int|Submission $submission
     */
    public function __construct($submission) {
        if ($submission instanceof Submission) {
            if ($submission->getId() === null) {
                throw new \InvalidArgumentException('The given submission must have an ID');
            }

            $this->submissionId = $submission->getId();
        } elseif (is_scalar($submission)) {
            $this->submissionId = (int) $submission;
        } else {
            throw new \TypeError(sprintf(
                '$submission must be integer or instance of %s, %s given',
                Submission::class,
                get_debug_type($submission),
            ));
        }
    }

    public function getSubmissionId(): int {
        return $this->submissionId;
    }
}
