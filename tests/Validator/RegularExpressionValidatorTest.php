<?php

namespace App\Tests\Validator;

use App\Validator\RegularExpression;
use App\Validator\RegularExpressionValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\RegularExpressionValidator
 */
class RegularExpressionValidatorTest extends ConstraintValidatorTestCase {
    public function testNoViolationOnNull(): void {
        $this->validator->validate(null, new RegularExpression());

        $this->assertNoViolation();
    }

    public function testNoViolationOnValidRegex(): void {
        $this->validator->validate('foo', new RegularExpression());

        $this->assertNoViolation();
    }

    public function testRaiseOnEmptyMatch(): void {
        $constraint = new RegularExpression();
        $this->validator->validate('()', $constraint);

        $this->buildViolation($constraint->mustNotMatchEmptyMessage)
            ->setCode(RegularExpression::MUST_NOT_MATCH_EMPTY_ERROR)
            ->assertRaised();
    }

    public function testRaiseOnInvalidRegex(): void {
        $constraint = new RegularExpression();
        $this->validator->validate('?', $constraint);

        $this->buildViolation($constraint->invalidMessage)
            ->setCode(RegularExpression::INVALID_ERROR)
            ->assertRaised();
    }

    public function testThrowsOnNonStringableValue(): void {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], new RegularExpression());
    }

    public function testThrowsOnWrongConstraintType(): void {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotNull());
    }

    protected function createValidator(): RegularExpressionValidator {
        return new RegularExpressionValidator();
    }
}
