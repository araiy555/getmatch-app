<?php

namespace App\Entity\Traits;

use App\Entity\Contracts\Votable;
use App\Entity\User;
use App\Entity\Vote;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

trait VotableTrait {
    /**
     * @return Collection|Selectable|Vote[]
     */
    abstract protected function getVotes(): Collection;

    public function getDownvotes(): int {
        $this->getVotes()->get(-1); // hydrate collection

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('upvote', false));

        return \count($this->getVotes()->matching($criteria));
    }

    public function getNetScore(): int {
        return $this->getUpvotes() - $this->getDownvotes();
    }

    public function getUpvotes(): int {
        $this->getVotes()->get(-1); // hydrate collection

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('upvote', true));

        return \count($this->getVotes()->matching($criteria));
    }

    public function getUserChoice(User $user): int {
        $vote = $this->getUserVote($user);

        return $vote ? $vote->getChoice() : Votable::VOTE_NONE;
    }

    public function getUserVote(User $user): ?Vote {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        return $this->getVotes()->matching($criteria)->first() ?: null;
    }
}
