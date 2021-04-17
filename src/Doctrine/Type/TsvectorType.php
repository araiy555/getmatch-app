<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class TsvectorType extends Type {
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string {
        return 'TSVECTOR';
    }

    public function getName(): string {
        return 'tsvector';
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string {
        return sprintf('TO_TSVECTOR(%s)', $sqlExpr);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
        return true;
    }
}
