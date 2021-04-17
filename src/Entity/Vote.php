<?php

namespace App\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\Exception\BadVoteChoiceException;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base class for all vote entities.
 *
 * @ORM\MappedSuperclass()
 */
abstract class Vote {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $upvote;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    /**
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $ip;

    /**
     * @param int $choice one of Votable::VOTE_UP or
     *                    Votable::VOTE_DOWN
     *
     * @throws \InvalidArgumentException if $choice is bad
     * @throws \InvalidArgumentException if IP address isn't valid
     */
    public function __construct(int $choice, User $user, ?string $ip) {
        $this->user = $user;
        $this->setChoice($choice);
        $this->setIp($ip);
        $this->timestamp = new \DateTimeImmutable('@'.time());
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getChoice(): int {
        return $this->upvote
            ? Votable::VOTE_UP
            : Votable::VOTE_DOWN;
    }

    public function setChoice(int $choice): void {
        if ($choice === Votable::VOTE_NONE) {
            throw new BadVoteChoiceException('A vote entity cannot have a "none" status');
        }

        if ($choice !== Votable::VOTE_UP && $choice !== Votable::VOTE_DOWN) {
            throw new BadVoteChoiceException('Unknown choice');
        }

        $this->upvote = $choice === Votable::VOTE_UP;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getIp(): ?string {
        return $this->ip;
    }

    public function setIp(?string $ip): void {
        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Bad IP address');
        }

        $this->ip = $this->user->isWhitelistedOrAdmin() ? null : $ip;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    /**
     * Legacy getter needed for `Selectable` compatibility.
     *
     * @internal
     */
    public function getUpvote(): bool {
        return $this->upvote;
    }
}
