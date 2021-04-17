<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ForumLogSubmissionRestored extends ForumLogEntry {
    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $author;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\ManyToOne(targetEntity="Submission")
     *
     * @var Submission|null
     */
    private $submission;

    public function __construct(Submission $submission, User $user) {
        parent::__construct($submission->getForum(), $user);

        $this->submission = $submission;
        $this->title = $submission->getTitle();
        $this->author = $submission->getUser();
    }

    public function getSubmission(): ?Submission {
        return $this->submission;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getAuthor(): User {
        return $this->author;
    }

    public function getAction(): string {
        return 'submission_restored';
    }
}
