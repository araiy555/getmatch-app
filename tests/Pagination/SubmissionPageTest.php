<?php

namespace App\Tests\Pagination;

use App\Entity\Submission;
use App\Pagination\SubmissionPage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\SubmissionPage
 */
class SubmissionPageTest extends TestCase {
    /**
     * @dataProvider provideValidQueries
     */
    public function testValidQueries(string $sortBy, string $field, string $value): void {
        $page = new SubmissionPage($sortBy);
        $this->assertTrue($page->isFieldValid($field, $value));
    }

    public function provideValidQueries(): \Generator {
        foreach (Submission::SORT_OPTIONS as $sortBy) {
            yield [$sortBy, 'id', '4201'];
        }

        yield [Submission::SORT_HOT, 'ranking', '12512512'];
        yield [Submission::SORT_TOP, 'netScore', '1241'];
        yield [Submission::SORT_CONTROVERSIAL, 'netScore', '1241'];
        yield [Submission::SORT_ACTIVE, 'lastActive', '2020-02-02T02:02:02Z'];
        yield [Submission::SORT_MOST_COMMENTED, 'commentCount', '91259'];
    }

    /**
     * @dataProvider provideInvalidQueries
     */
    public function testInvalidQueries(string $sortBy, string $field, string $value): void {
        $page = new SubmissionPage($sortBy);
        $this->assertFalse($page->isFieldValid($field, $value));
    }

    public function provideInvalidQueries(): \Generator {
        yield [Submission::SORT_NEW, 'id', PHP_INT_MAX.'1'];
        yield [Submission::SORT_NEW, 'id', '4.20'];
        yield [Submission::SORT_HOT, 'ranking', PHP_INT_MAX.'1'];
        yield [Submission::SORT_HOT, 'ranking', '4.20'];
        yield [Submission::SORT_TOP, 'netScore', PHP_INT_MAX.'1'];
        yield [Submission::SORT_TOP, 'netScore', '4.20'];
        yield [Submission::SORT_CONTROVERSIAL, 'netScore', PHP_INT_MAX.'1'];
        yield [Submission::SORT_CONTROVERSIAL, 'netScore', '4.20'];
        yield [Submission::SORT_MOST_COMMENTED, 'commentCount', PHP_INT_MAX.'1'];
        yield [Submission::SORT_MOST_COMMENTED, 'commentCount', '4.20'];
        yield [Submission::SORT_ACTIVE, 'lastActive', '2020-02-02T0202:02Z'];
        yield [Submission::SORT_ACTIVE, 'lastActive', 'wesehkjsk'];
        yield [Submission::SORT_ACTIVE, 'asfasf', 'fas'];
    }
}
