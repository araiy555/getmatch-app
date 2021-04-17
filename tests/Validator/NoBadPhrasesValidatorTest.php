<?php

namespace App\Tests\Validator;

use App\Utils\BadPhraseMatcher;
use App\Validator\IpWithCidr;
use App\Validator\NoBadPhrases;
use App\Validator\NoBadPhrasesValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\NoBadPhrasesValidator
 */
class NoBadPhrasesValidatorTest extends ConstraintValidatorTestCase {
    /**
     * @var BadPhraseMatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matcher;

    protected function setUp(): void {
        $this->matcher = $this->createMock(BadPhraseMatcher::class);

        parent::setUp();
    }

    protected function createValidator(): NoBadPhrasesValidator {
        return new NoBadPhrasesValidator($this->matcher);
    }

    public function testMatchingInputWillRaise(): void {
        $this->matcher
            ->expects($this->once())
            ->method('matches')
            ->with('fly')
            ->willReturn(true);

        $constraint = new NoBadPhrases();
        $this->validator->validate('fly', $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
            ->assertRaised();
    }

    public function testNonMatchingInputWillNotRaise(): void {
        $this->matcher
            ->expects($this->once())
            ->method('matches')
            ->with('bee')
            ->willReturn(false);

        $this->validator->validate('bee', new NoBadPhrases());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideEmptyInputs
     * @param mixed $emptyInput
     */
    public function testEmptyInputWillNotRaise($emptyInput): void {
        $this->matcher
            ->expects($this->never())
            ->method('matches');

        $this->validator->validate($emptyInput, new NoBadPhrases());

        $this->assertNoViolation();
    }

    public function testThrowsOnNonScalarNonStringableValue(): void {
        $this->matcher
            ->expects($this->never())
            ->method('matches');

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate([], new NoBadPhrases());
    }

    public function testThrowsOnWrongConstraintType(): void {
        $this->matcher
            ->expects($this->never())
            ->method('matches');

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('aa', new IpWithCidr());
    }

    public function provideEmptyInputs(): iterable {
        yield [null];
        yield [''];
        yield [false];
        yield [new class() {
            public function __toString(): string {
                return '';
            }
        }];
    }
}
