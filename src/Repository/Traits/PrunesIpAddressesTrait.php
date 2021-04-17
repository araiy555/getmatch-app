<?php

namespace App\Repository\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

trait PrunesIpAddressesTrait {
    public function pruneIpAddresses(?\DateTimeImmutable $olderThan): int {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update($this->getClassName(), 'e')
            ->set('e.'.$this->getIpAddressField(), 'NULL')
            ->where('e.'.$this->getIpAddressField().' IS NOT NULL');

        if ($olderThan) {
            $qb->andWhere('e.'.$this->getTimestampField().' <= ?1')
                ->setParameter(1, $olderThan, Types::DATETIMETZ_IMMUTABLE);
        }

        return $qb->getQuery()->execute();
    }

    protected function getTimestampField(): string {
        return 'timestamp';
    }

    protected function getIpAddressField(): string {
        return 'ip';
    }

    /**
     * @return EntityManagerInterface
     */
    abstract protected function getEntityManager();

    /**
     * @return string
     */
    abstract protected function getClassName();
}
