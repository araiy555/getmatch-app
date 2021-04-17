<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a regular expression without start & end delimiters.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class RegularExpression extends Constraint {
    public const INVALID_ERROR = '4c80a7f3-7ad7-4821-8a0f-df7f87845779';
    public const MUST_NOT_MATCH_EMPTY_ERROR = '4631b2b2-5873-455f-bb1b-a95f9c90ce1c';

    public $invalidMessage = 'regex.invalid';
    public $mustNotMatchEmptyMessage = 'regex.must_not_match_empty';

    protected static $errorNames = [
        self::INVALID_ERROR => 'INVALID_ERROR',
        self::MUST_NOT_MATCH_EMPTY_ERROR => 'MUST_NOT_MATCH_EMPTY_ERROR',
    ];
}
