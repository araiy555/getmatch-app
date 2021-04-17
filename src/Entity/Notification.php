<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NotificationRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="notification_type", type="text")
 * @ORM\DiscriminatorMap({
 *     "comment": "CommentNotification",
 *     "comment_mention": "CommentMention",
 *     "message": "MessageNotification",
 *     "submission_mention": "SubmissionMention",
 * })
 */
abstract class Notification {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="notifications")
     *
     * @var User
     */
    private $user;

    public function __construct(User $receiver) {
        $this->id = Uuid::uuid4();
        $this->timestamp = new \DateTimeImmutable('@'.time());
        $this->user = $receiver;

        $receiver->sendNotification($this);
    }

    abstract public function getType(): string;

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getUser(): User {
        if (!$this->user) {
            throw new \BadMethodCallException(
                'The user was detached from the notification',
            );
        }

        return $this->user;
    }

    public function detach(): void {
        if ($this->user) {
            $this->user->clearNotification($this);
            $this->user = null;
        }
    }
}
