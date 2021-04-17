<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="forum_user_idx", columns={"forum_id", "user_id"})
 * })
 */
class ForumSubscription {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var Uuid
     */
    private $id;

    /**
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="subscriptions")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\JoinColumn(name="forum_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="Forum", inversedBy="subscriptions")
     *
     * @var Forum
     */
    private $forum;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $subscribedAt;

    public function __construct(User $user, Forum $forum) {
        $this->id = Uuid::uuid4();
        $this->user = $user;
        $this->forum = $forum;
        $this->subscribedAt = new \DateTimeImmutable('@'.time());
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getForum(): Forum {
        return $this->forum;
    }

    public function getSubscribedAt(): \DateTimeImmutable {
        return $this->subscribedAt;
    }
}
