<?php

namespace App\Tests\Entity\Constants;

use App\Entity\Constants\SubmissionLinkDestination;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Constants\SubmissionLinkDestination
 */
class SubmissionLinkDestinationTest extends TestCase {
    /**
     * @dataProvider provideValidDestinations
     */
    public function testAssertsValidDestination(string $destination): void {
        $this->expectNotToPerformAssertions();

        SubmissionLinkDestination::assertValidDestination($destination);
    }

    public function provideValidDestinations(): \Generator {
        foreach (SubmissionLinkDestination::OPTIONS as $value) {
            yield [$value];
        }
    }

    public function testThrowsWhenDestinationIsInvalid(): void {
        $this->expectException(\InvalidArgumentException::class);

        SubmissionLinkDestination::assertValidDestination('not valid');
    }
}
