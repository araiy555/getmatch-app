<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class NoBadPhrases extends Constraint {
    public const CONTAINS_BAD_PHRASE_ERROR = 'ddc45da1-2ea3-49d7-8ecb-79e02d04effa';

    protected static $errorNames = [
        self::CONTAINS_BAD_PHRASE_ERROR => 'CONTAINS_BAD_PHRASE_ERROR',
    ];

    public $message = 'bad_phrase.match';

    public function getTargets(): array {
        return [Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT];
    }
}
