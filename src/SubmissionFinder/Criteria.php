<?php

namespace App\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;

class Criteria {
    public const VIEW_FEATURED = 1;
    public const VIEW_SUBSCRIBED = 2;
    public const VIEW_ALL = 3;
    public const VIEW_MODERATED = 4;
    public const VIEW_FORUMS = 5;
    public const VIEW_USERS = 6;

    public const EXCLUDE_HIDDEN_FORUMS = 1;
    public const EXCLUDE_BLOCKED_USERS = 2;

    /**
     * @var string
     */
    private $sortBy;

    /**
     * @var string|null
     */
    private $visibility = Submission::VISIBILITY_VISIBLE;

    private $view = self::VIEW_ALL;

    /**
     * @var Forum[]
     */
    private $forums = [];

    private $exclude = 0;

    /**
     * @var User[]
     */
    private $users = [];

    private $stickiesFirst = false;

    private $maxPerPage = 25;

    public function __construct(?string $sortBy) {
        if ($sortBy && !\in_array($sortBy, Submission::SORT_OPTIONS, true)) {
            throw new \InvalidArgumentException("Unknown sort mode '$sortBy'");
        }

        $this->sortBy = $sortBy;
    }

    /**
     * @return string|null One of App\Entity\Submission::SORT_* fields, or NULL
     */
    public function getSortBy(): ?string {
        return $this->sortBy;
    }

    /**
     * @return int One of App\SubmissionFinder\Criteria::VIEW_* constants
     */
    public function getView(): int {
        return $this->view;
    }

    public function showFeatured(): self {
        return $this->setView(self::VIEW_FEATURED);
    }

    public function showSubscribed(): self {
        return $this->setView(self::VIEW_SUBSCRIBED);
    }

    public function showModerated(): self {
        return $this->setView(self::VIEW_MODERATED);
    }

    public function getForums(): array {
        if ($this->view !== self::VIEW_FORUMS) {
            throw new \BadMethodCallException('showForums() was not called');
        }

        return $this->forums;
    }

    public function showForums(Forum ...$forums): self {
        $this->setView(self::VIEW_FORUMS);
        $this->forums = $forums;

        return $this;
    }

    public function getUsers(): array {
        if ($this->view !== self::VIEW_USERS) {
            throw new \BadMethodCallException('showUsers() was not called');
        }

        return $this->users;
    }

    public function showUsers(User ...$users): self {
        $this->setView(self::VIEW_USERS);
        $this->users = $users;

        return $this;
    }

    /**
     * @return int exclusions, as a bit field
     */
    public function getExclusions(): int {
        return $this->exclude;
    }

    /**
     * Exclude forums the user has marked as hidden.
     */
    public function excludeHiddenForums(): self {
        if ($this->exclude & self::EXCLUDE_HIDDEN_FORUMS) {
            throw new \BadMethodCallException('This method was already called');
        }

        $this->exclude |= self::EXCLUDE_HIDDEN_FORUMS;

        return $this;
    }

    public function excludeBlockedUsers(): self {
        if ($this->exclude & self::EXCLUDE_BLOCKED_USERS) {
            throw new \BadMethodCallException('This method was already called');
        }

        $this->exclude |= self::EXCLUDE_BLOCKED_USERS;

        return $this;
    }

    public function getStickiesFirst(): bool {
        return $this->stickiesFirst;
    }

    public function stickiesFirst(): self {
        $this->stickiesFirst = true;

        return $this;
    }

    public function getMaxPerPage(): int {
        return $this->maxPerPage;
    }

    public function setMaxPerPage(int $maxPerPage): self {
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    public function getVisibility(): string {
        return $this->visibility;
    }

    public function trashed(): self {
        if ($this->visibility !== Submission::VISIBILITY_VISIBLE) {
            throw new \BadMethodCallException('Cannot call multiple visibility methods');
        }

        $this->visibility = Submission::VISIBILITY_TRASHED;

        return $this;
    }

    private function setView(int $view): self {
        if ($this->view !== self::VIEW_ALL) {
            throw new \BadMethodCallException(
                'You cannot call multiple '.__CLASS__.'::show* methods'
            );
        }

        $this->view = $view;

        return $this;
    }
}
