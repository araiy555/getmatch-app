<?php

namespace App\Form\Model;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class ForumBanData {
    /**
     * @Assert\NotBlank(groups={"ban", "unban"})
     * @Assert\Length(max=300, groups={"ban", "unban"})
     *
     * @var string|null
     */
    private $reason;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expires;

    public function toBan(Forum $forum, User $user, User $bannedBy): ForumBan {
        return new ForumBan($forum, $user, $this->reason, true, $bannedBy, $this->expires);
    }

    public function toUnban(Forum $forum, User $user, User $bannedBy): ForumBan {
        return new ForumBan($forum, $user, $this->reason, false, $bannedBy);
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function setReason(?string $reason): void {
        $this->reason = $reason;
    }

    public function getExpires(): ?\DateTimeImmutable {
        return $this->expires;
    }

    public function setExpires(?\DateTimeInterface $expires): void {
        if ($expires instanceof \DateTime) {
            $expires = \DateTimeImmutable::createFromMutable($expires);
        }

        $this->expires = $expires;
    }
}
