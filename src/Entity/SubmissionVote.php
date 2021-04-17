<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubmissionVoteRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *         name="submission_user_vote_idx",
 *         columns={"submission_id", "user_id"}
 *     )
 * })
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="user", inversedBy="submissionVotes")
 * })
 */
class SubmissionVote extends Vote {
    /**
     * @ORM\JoinColumn(name="submission_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="Submission", inversedBy="votes")
     *
     * @var Submission
     */
    private $submission;

    public function __construct(int $choice, User $user, ?string $ip, Submission $submission) {
        parent::__construct($choice, $user, $ip);

        $this->submission = $submission;

        $submission->addVote($this);
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }
}
