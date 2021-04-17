<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommentVoteRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *         name="comment_user_vote_idx",
 *         columns={"comment_id", "user_id"}
 *     )
 * })
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="user", inversedBy="commentVotes")
 * })
 */
class CommentVote extends Vote {
    /**
     * @ORM\JoinColumn(name="comment_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="votes")
     *
     * @var Comment
     */
    private $comment;

    public function __construct(int $choice, User $user, ?string $ip, Comment $comment) {
        parent::__construct($choice, $user, $ip);

        $this->comment = $comment;

        $comment->addVote($this);
    }

    public function getComment(): Comment {
        return $this->comment;
    }
}
