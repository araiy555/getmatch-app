<?php

namespace App\Tests\Fixtures;

use App\Entity\Contracts\Votable;
use App\Entity\Traits\VotableTrait;
use App\Entity\User;
use App\Entity\Vote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class VotableStub implements Votable {
    use VotableTrait;

    private $votes;

    public function __construct() {
        $this->votes = new ArrayCollection();
    }

    public function getVotes(): Collection {
        return $this->votes;
    }

    public function createVote(int $choice, User $user, ?string $ip): Vote {
        return new class($choice, $user, $ip) extends Vote {
        };
    }

    public function addVote(Vote $vote): void {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
        }
    }

    public function removeVote(Vote $vote): void {
        $this->votes->removeElement($vote);
    }
}
