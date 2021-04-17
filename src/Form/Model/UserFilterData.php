<?php

namespace App\Form\Model;

use Doctrine\Common\Collections\Criteria;

class UserFilterData {
    public const ROLE_ANY = 'any';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_WHITELISTED = 'whitelisted';
    public const ROLE_NONE = 'none';

    public const ORDER_CREATED = 'created';
    public const ORDER_USERNAME = 'username';

    /**
     * @var string
     */
    private $role = self::ROLE_ANY;

    /**
     * @var string
     */
    private $orderBy = self::ORDER_CREATED;

    public function buildCriteria(): Criteria {
        $criteria = Criteria::create();

        switch ($this->role) {
        case self::ROLE_ANY:
            // do nothing
            break;

        case self::ROLE_ADMIN:
            $criteria->where(Criteria::expr()->eq('admin', true));
            break;

        case self::ROLE_WHITELISTED:
            $criteria->where(Criteria::expr()->eq('whitelisted', true));
            $criteria->andWhere(Criteria::expr()->eq('admin', false));
            break;

        case self::ROLE_NONE:
            $criteria->where(Criteria::expr()->eq('admin', false));
            $criteria->andWhere(Criteria::expr()->eq('whitelisted', false));
            break;

        default:
            throw new \DomainException('Unknown role choice');
        }

        switch ($this->orderBy) {
        case self::ORDER_CREATED:
            $criteria
                ->orderBy(['id' => 'DESC']);
            break;

        case self::ORDER_USERNAME:
            $criteria
                ->orderBy(['normalizedUsername' => 'ASC']);
            break;

        default:
            throw new \DomainException('Unknown order choice');
        }

        return $criteria;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function setRole(string $role): void {
        $this->role = $role;
    }

    public function getOrderBy(): string {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): void {
        $this->orderBy = $orderBy;
    }
}
