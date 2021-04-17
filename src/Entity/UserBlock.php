<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"blocker_id", "blocked_id"}, name="user_blocks_blocker_blocked_idx")
 * })
 */
class UserBlock {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var Uuid
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="blocks")
     *
     * @var User
     */
    private $blocker;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $blocked;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $comment;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    public function __construct(User $blocker, User $blocked, ?string $comment) {
        if ($blocker === $blocked) {
            throw new \DomainException('cannot block self');
        }

        $this->id = Uuid::uuid4();
        $this->blocker = $blocker;
        $this->blocked = $blocked;
        $this->comment = $comment;
        $this->timestamp = new \DateTimeImmutable('@'.time());
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getBlocker(): User {
        return $this->blocker;
    }

    public function getBlocked(): User {
        return $this->blocked;
    }

    public function getComment(): ?string {
        return $this->comment;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }
}
