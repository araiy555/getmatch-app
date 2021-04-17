<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;

final class InetType extends Type {
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string {
        return 'INET';
    }

    public function getName(): string {
        return 'inet';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string {
        if (!$platform instanceof PostgreSqlPlatform) {
            throw new \InvalidArgumentException('Platform must be PostgreSQL');
        }

        if ($value === null) {
            return null;
        }

        [$ip, $cidr] = array_pad(explode('/', $value), 2, null);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }

        if ($cidr !== null) {
            if (!is_numeric($cidr) || !\is_int(+$cidr)) {
                throw new \InvalidArgumentException('CIDR must be integer');
            }

            $length = strpos($ip, ':') !== false ? 128 : 32;

            if ($cidr < 0 || $cidr > $length) {
                throw new \InvalidArgumentException("CIDR must be between 0 and $length");
            }

            return sprintf('%s/%s', $ip, $cidr);
        }

        return $ip;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
        return true;
    }
}
