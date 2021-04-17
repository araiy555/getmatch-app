<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RegularExpressionValidator extends ConstraintValidator {
    public function validate($value, Constraint $constraint): void {
        if (!$constraint instanceof RegularExpression) {
            throw new UnexpectedTypeException($value, RegularExpression::class);
        }

        if ($value === null) {
            return;
        }

        if (!\is_string($value) && !\is_callable([$value, '__toString'])) {
            throw new UnexpectedTypeException($value, 'Stringable');
        }

        $value = (string) $value;

        $return = @preg_match('@'.addcslashes($value, '@').'@u', '');

        if ($return === 1) {
            $this->context
                ->buildViolation($constraint->mustNotMatchEmptyMessage)
                ->setCode(RegularExpression::MUST_NOT_MATCH_EMPTY_ERROR)
                ->addViolation();
        } elseif ($return !== 0) {
            $this->context
                ->buildViolation($constraint->invalidMessage)
                ->setCode(RegularExpression::INVALID_ERROR)
                ->addViolation();
        }
    }
}
