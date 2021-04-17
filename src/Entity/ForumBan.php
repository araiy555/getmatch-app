<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents a ban or unban action that applies to a user and a forum.
 *
 * @ORM\Entity(repositoryClass="App\Repository\ForumBanRepository")
 */
class ForumBan {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var Uuid
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Forum", inversedBy="bans")
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
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $reason;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $banned;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $bannedBy;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    /**
     * @ORM\Column(name="expires_at", type="datetimetz_immutable", nullable=true)
     *
     * @var \DateTimeImmutable|null
     */
    private $expires;

    public function __construct(
        Forum $forum,
        User $user,
        string $reason,
        bool $banned,
        User $bannedBy,
        \DateTimeInterface $expires = null
    ) {
        if (!$banned && $expires) {
            throw new \DomainException('Unbans cannot have expiry times');
        }

        if ($expires instanceof \DateTime) {
            $expires = \DateTimeImmutable::createFromMutable($expires);
        }

        $this->id = Uuid::uuid4();
        $this->forum = $forum;
        $this->user = $user;
        $this->reason = $reason;
        $this->banned = $banned;
        $this->bannedBy = $bannedBy;
        $this->expires = $expires;
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

    public function getReason(): string {
        return $this->reason;
    }

    public function isBan(): bool {
        return $this->banned;
    }

    public function getBannedBy(): User {
        return $this->bannedBy;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getExpires(): ?\DateTimeImmutable {
        return $this->expires;
    }

    public function isExpired(): bool {
        if ($this->expires === null) {
            return false;
        }

        return $this->expires->getTimestamp() < time();
    }
}
