<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Rate limit by user and IP address.
 *
 * @Annotation
 * @Target("CLASS")
 */
class RateLimit extends Constraint {
    public const RATE_LIMITED_ERROR = 'bf95a6b8-f86d-4c9c-80ba-db0f8630fb27';

    protected static $errorNames = [
        self::RATE_LIMITED_ERROR => 'RATE_LIMITED_ERROR',
    ];

    public $entityClass;
    public $errorPath = '';
    public $message = 'ratelimit.error';
    public $max;
    public $timestampField = 'timestamp';
    public $userField = 'user';
    public $ipField = 'ip';

    /**
     * {@link \DateInterval::createFromDateString()} compatible interval.
     *
     * @var string
     */
    public $period;

    public function __construct($options = null) {
        parent::__construct($options);

        $period = \DateInterval::createFromDateString($options['period']);

        $d2 = new \DateTimeImmutable('@'.time());
        $d1 = $d2->sub($period);

        if ($d2 <= $d1) {
            throw new ConstraintDefinitionException(
                'The period specified is not a valid interval'
            );
        }
    }

    public function getRequiredOptions(): array {
        return ['max', 'period'];
    }

    public function getTargets(): array {
        return [Constraint::CLASS_CONSTRAINT];
    }
}
