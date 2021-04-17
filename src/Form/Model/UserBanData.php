<?php

namespace App\Form\Model;

use App\Entity\IpBan;
use App\Entity\User;
use App\Entity\UserBan;
use App\Validator\IpWithCidr;
use Symfony\Component\Validator\Constraints as Assert;

class UserBanData {
    /**
     * @Assert\All({
     *     @IpWithCidr(groups={"ban_ip"})
     * })
     * @Assert\NotBlank(groups={"ban_ip"}),
     *
     * @var string[]|iterable
     */
    private $ips;

    /**
     * @Assert\Length(max=300, groups={"ban_user", "ban_ip"})
     * @Assert\NotBlank(groups={"ban_user", "ban_ip"})
     */
    public $reason;

    public $expires;

    public function __construct(iterable $ips = null) {
        $this->ips = $ips;
    }

    public function toUserBan(User $user, User $bannedBy, bool $ban): UserBan {
        return new UserBan($user, $this->reason, $ban, $bannedBy, $this->expires);
    }

    /**
     * @return IpBan[]
     */
    public function toIpBans(User $user, User $bannedBy): iterable {
        foreach ($this->ips as $ip) {
            yield new IpBan($ip, $this->reason, $user, $bannedBy, $this->expires);
        }
    }

    public function getIps(): iterable {
        return $this->ips;
    }

    public function setIps(iterable $ips): void {
        $this->ips = $ips;
    }
}
