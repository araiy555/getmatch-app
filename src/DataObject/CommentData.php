<?php

namespace App\DataObject;

use App\Entity\Comment;
use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Validator\NoBadPhrases;
use App\Validator\NotForumBanned;
use App\Validator\RateLimit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @RateLimit(period="5 minutes", max=10, groups={"create"}, entityClass=Comment::class, errorPath="body")
 * @NotForumBanned(forumPath="submission.forum", errorPath="body")
 */
class CommentData implements NormalizeMarkdownInterface {
    /**
     * @Groups({"comment:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @Assert\NotBlank(message="comment.empty")
     * @Assert\Regex("/[[:graph:]]/u", message="comment.empty")
     * @Assert\Length(max=Comment::MAX_BODY_LENGTH)
     * @NoBadPhrases()
     *
     * @Groups({"comment:read", "comment:update"})
     *
     * @var string|null
     */
    private $body;

    /**
     * @Groups("comment:read")
     *
     * @var \DateTimeImmutable|null
     */
    private $timestamp;

    /**
     * @Groups("comment:read")
     *
     * @var User|null
     */
    private $user;

    /**
     * @Groups("comment:read")
     *
     * @var Submission|null
     */
    private $submission;

    /**
     * @Groups("comment:read")
     *
     * @var int|null
     */
    private $parentId;

    /**
     * @Groups("comment:nested")
     *
     * @var Comment[]
     */
    private $replies = [];

    /**
     * @Groups("comment:read")
     *
     * @var int|null
     */
    private $replyCount;

    /**
     * @Groups("comment:read")
     *
     * @var string
     */
    private $visibility = VisibilityInterface::VISIBILITY_VISIBLE;

    /**
     * @Groups("comment:read")
     *
     * @var \DateTimeImmutable|null
     */
    private $editedAt;

    /**
     * @Groups("comment:read")
     *
     * @var bool
     */
    private $moderated = false;

    /**
     * @Groups("comment:read")
     *
     * @var string|null
     */
    private $userFlag = UserFlags::FLAG_NONE;

    /**
     * @Groups("comment:read")
     *
     * @var int
     */
    private $netScore = 0;

    /**
     * @Groups("comment:read")
     *
     * @var int|null
     */
    private $upvotes;

    /**
     * @Groups("comment:read")
     *
     * @var int
     */
    private $downvotes;

    public function __construct(Comment $comment = null) {
        if ($comment) {
            $this->id = $comment->getId();
            $this->body = $comment->getBody();
            $this->timestamp = $comment->getTimestamp();
            $this->user = $comment->getUser();
            $this->submission = $comment->getSubmission();
            $this->parentId = $comment->getParent() ? $comment->getParent()->getId() : null;
            $this->replies = $comment->getChildren();
            $this->replyCount = $comment->getReplyCount();
            $this->visibility = $comment->getVisibility();
            $this->editedAt = $comment->getEditedAt();
            $this->moderated = $comment->isModerated();
            $this->userFlag = $comment->getUserFlag();
            $this->netScore = $comment->getNetScore();
            $this->upvotes = $comment->getUpvotes();
            $this->downvotes = $comment->getDownvotes();
        }
    }

    /**
     * @param Submission|Comment $parent
     */
    public function toComment($parent, User $user, ?string $ip): Comment {
        $comment = new Comment($this->body, $user, $parent, $ip);
        $comment->setUserFlag($this->userFlag);

        return $comment;
    }

    public function updateComment(Comment $comment, User $editingUser): void {
        $comment->setUserFlag($this->userFlag);

        if ($this->body !== $comment->getBody()) {
            $comment->setBody($this->body);
            $comment->updateEditedAt();

            if (!$comment->isModerated()) {
                $comment->setModerated($comment->getUser() !== $editingUser);
            }
        }
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getBody(): ?string {
        return $this->body;
    }

    public function setBody(?string $body): void {
        $this->body = $body;
    }

    public function getTimestamp(): ?\DateTimeImmutable {
        return $this->timestamp;
    }

    public function getUser(): ?User {
        return $this->user;
    }

    public function getSubmission(): ?Submission {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): void {
        $this->submission = $submission;
    }

    public function getParentId(): ?int {
        return $this->parentId;
    }

    public function getReplies(): array {
        return $this->replies;
    }

    public function getReplyCount(): int {
        return $this->replyCount;
    }

    public function getVisibility(): string {
        return $this->visibility;
    }

    public function getEditedAt(): ?\DateTimeImmutable {
        return $this->editedAt;
    }

    public function isModerated(): bool {
        return $this->moderated;
    }

    public function getUserFlag(): ?string {
        return $this->userFlag;
    }

    public function setUserFlag(?string $userFlag): void {
        $this->userFlag = $userFlag;
    }

    public function getNetScore(): int {
        return $this->netScore;
    }

    public function getUpvotes(): ?int {
        return $this->upvotes;
    }

    public function getDownvotes(): int {
        return $this->downvotes;
    }

    public function getMarkdownFields(): iterable {
        yield 'body';
    }
}
