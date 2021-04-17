<?php

namespace App\DataTransfer;

use App\Entity\Contracts\Votable;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class VoteManager {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function vote(Votable $votable, User $user, int $choice, ?string $ip): void {
        $vote = $votable->getUserVote($user);

        if ($vote) {
            if ($choice === Votable::VOTE_NONE) {
                $votable->removeVote($vote);
                $this->entityManager->remove($vote);
            } elseif ($choice !== $vote->getChoice()) {
                $vote->setChoice($choice);
                $votable->addVote($vote);
            }
        } elseif ($choice !== Votable::VOTE_NONE) {
            $vote = $votable->createVote($choice, $user, $ip);

            $votable->addVote($vote);
            $this->entityManager->persist($vote);
        }

        $this->entityManager->flush();
    }
}
