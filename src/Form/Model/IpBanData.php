<?php

namespace App\Form\Model;

use App\Entity\IpBan;
use App\Entity\User;
use App\Validator\IpWithCidr;
use Symfony\Component\Validator\Constraints as Assert;

class IpBanData {
    /**
     * @Assert\NotBlank()
     * @IpWithCidr()
     *
     * @var string|null
     */
    public $ip;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @var string|null
     */
    public $reason;

    /**
     * @var \DateTimeInterface|null
     */
    public $expires;

    /**
     * @var User|null
     */
    public $user;

    public function toIpBan(User $bannedBy): IpBan {
        return new IpBan(
            $this->ip,
            $this->reason,
            $this->user,
            $bannedBy,
            $this->expires
        );
    }
}
