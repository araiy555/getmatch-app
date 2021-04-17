<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForumLogEntryRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="action_type", type="text")
 * @ORM\DiscriminatorMap({
 *     "comment_deletion": "ForumLogCommentDeletion",
 *     "comment_restored": "ForumLogCommentRestored",
 *     "submission_deletion": "ForumLogSubmissionDeletion",
 *     "ban": "ForumLogBan",
 *     "submission_lock": "ForumLogSubmissionLock",
 *     "submission_restored": "ForumLogSubmissionRestored",
 * })
 */
abstract class ForumLogEntry {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var Uuid
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Forum", inversedBy="logEntries")
     *
     * @var Forum
     */
    private $forum;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $wasAdmin;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    abstract public function getAction(): string;

    public function __construct(Forum $forum, User $user) {
        $this->id = Uuid::uuid4();
        $this->forum = $forum;
        $this->user = $user;
        $this->wasAdmin = !$forum->userIsModerator($user, false);
        $this->timestamp = new \DateTimeImmutable('@'.time());
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getForum(): Forum {
        return $this->forum;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function wasAdmin(): bool {
        return $this->wasAdmin;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }
}
