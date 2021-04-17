<?php

namespace App\Entity;

/**
 * Flags that apply to a submission or comment and which describes the role of
 * the poster.
 */
final class UserFlags {
    public const FLAGS = [
        self::FLAG_NONE,
        self::FLAG_MODERATOR,
        self::FLAG_ADMIN,
    ];

    public const FLAG_NONE = 'none';
    public const FLAG_MODERATOR = 'moderator';
    public const FLAG_ADMIN = 'admin';

    public static function checkUserFlag(string $userFlag): void {
        if (!\in_array($userFlag, self::FLAGS, true)) {
            throw new \InvalidArgumentException("Bad user flag '$userFlag'");
        }
    }
}
