<?php

namespace App\Repository\Contracts;

interface PrunesIpAddresses {
    /**
     * @return int The number of rows affected
     */
    public function pruneIpAddresses(?\DateTimeImmutable $olderThan): int;
}
