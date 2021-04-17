<?php

namespace App\Entity\Contracts;

interface VisibilityInterface {
    /**
     * The thing is displayed.
     */
    public const VISIBILITY_VISIBLE = 'visible';

    /**
     * The thing is permanently deleted, but a placeholder must remain for its
     * children.
     */
    public const VISIBILITY_SOFT_DELETED = 'soft_deleted';

    /**
     * The thing is hidden from public view by a moderator. It can be restored
     * or permanently deleted.
     */
    public const VISIBILITY_TRASHED = 'trashed';

    /**
     * @return string one of VISIBILITY_* constants
     */
    public function getVisibility(): string;

    public function isVisible(): bool;

    public function isTrashed(): bool;

    public function isSoftDeleted(): bool;

    public function softDelete(): void;

    public function trash(): void;

    public function restore(): void;
}
