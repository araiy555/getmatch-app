<?php

namespace App\Validator;

use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "ANNOTATION"})
 */
class IpWithCidr extends Constraint {
    public const INVALID_IP = '24672f6c-5a23-4067-8566-e44c35db9556';
    public const INVALID_CIDR = 'adf9db03-ccd6-43d2-8fd6-8dcc9ce9c3a1';
    public const MISSING_CIDR = '07301ef6-c958-430d-952e-2969a7d9cfb9';

    protected static $errorNames = [
        self::INVALID_IP => 'INVALID_IP',
        self::INVALID_CIDR => 'INVALID_CIDR',
        self::MISSING_CIDR => 'MISSING_CIDR',
    ];

    public $cidrOptional = true;

    public $invalidIpMessage = 'ip.invalid';
    public $invalidCidrMessage = 'ip.invalid_cidr_mask';
    public $missingCidrMessage = 'ip.cidr_missing';

    public function getTargets(): array {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
