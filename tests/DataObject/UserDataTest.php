<?php

namespace App\Tests\DataObject;

use App\DataObject\UserData;
use App\Entity\Constants\SubmissionLinkDestination;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataObject\UserData
 */
class UserDataTest extends TestCase {
    /**
     * @var UserData
     */
    private $dto;

    protected function setUp(): void {
        $this->dto = new UserData();
    }

    public function testSubmissionLinkDestinationAccessors(): void {
        $this->assertNull($this->dto->getSubmissionLinkDestination());
        $this->dto->setSubmissionLinkDestination(SubmissionLinkDestination::SUBMISSION);
        $this->assertSame(
            SubmissionLinkDestination::SUBMISSION,
            $this->dto->getSubmissionLinkDestination(),
        );
    }
}
