<?php

namespace App\Utils;

interface SluggerInterface {
    public const DEFAULT_MAX_LENGTH = 60;

    /**
     * Creates URL slugs.
     */
    public function slugify(
        string $input,
        int $maxLength = self::DEFAULT_MAX_LENGTH
    ): string;
}
