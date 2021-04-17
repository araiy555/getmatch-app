<?php

namespace App\Pagination;

use PagerWave\DefinitionGroupTrait;
use PagerWave\DefinitionInterface as Definition;
use PagerWave\Extension\Validator\ValidatingDefinitionInterface;

final class TimestampPage implements Definition, ValidatingDefinitionInterface {
    use DefinitionGroupTrait;

    public function getFieldNames(): array {
        return ['timestamp'];
    }

    public function isFieldDescending(string $fieldName): bool {
        return true;
    }

    public function isFieldValid(string $fieldName, $value): bool {
        return (bool) @\DateTime::createFromFormat(\DateTime::ATOM, $value);
    }
}
