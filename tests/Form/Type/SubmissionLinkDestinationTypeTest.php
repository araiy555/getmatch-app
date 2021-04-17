<?php

namespace App\Tests\Form\Type;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Form\Type\SubmissionLinkDestinationType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\SubmissionLinkDestinationType
 */
class SubmissionLinkDestinationTypeTest extends TypeTestCase {
    /** @var SubmissionLinkDestinationType $form */
    private $form;

    protected function setUp(): void {
        parent::setUp();

        $this->form = $this->factory->create(SubmissionLinkDestinationType::class);
    }

    /**
     * @dataProvider provideValues
     */
    public function testSubmit(string $value): void {
        $this->form->submit($value);

        $this->assertSame($value, $this->form->getData());
        $this->assertCount(0, $this->form->getErrors());
    }

    public function testFailsValidationOnInvalidValues(): void {
        $this->form->submit('invalid');

        $this->assertCount(1, $this->form->getErrors());
    }

    public function provideValues(): \Generator {
        yield [SubmissionLinkDestination::SUBMISSION];
        yield [SubmissionLinkDestination::URL];
    }
}
