<?php

namespace App\Entity\Constants;

final class SubmissionLinkDestination {
    public const SUBMISSION = 'submission';
    public const URL = 'url';

    public const OPTIONS = [self::SUBMISSION, self::URL];

    private function __construct() {
    }

    /**
     * @throws \InvalidArgumentException if destination is invalid
     */
    public static function assertValidDestination(string $destination): void {
        if (!\in_array($destination, self::OPTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Destination must be one of %s::* constants, "%s" given',
                __CLASS__,
                $destination,
            ));
        }
    }
}
