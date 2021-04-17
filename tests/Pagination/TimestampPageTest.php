<?php

namespace App\Tests\Pagination;

use App\Pagination\TimestampPage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\TimestampPage
 */
class TimestampPageTest extends TestCase {
    /**
     * @var TimestampPage
     */
    private $page;

    protected function setUp(): void {
        $this->page = new TimestampPage();
    }

    public function testDefinition(): void {
        $this->assertSame(['timestamp'], $this->page->getFieldNames());
        $this->assertTrue($this->page->isFieldDescending('timestamp'));
        $this->assertTrue($this->page->isFieldValid('timestamp', '2020-02-02T02:02:02Z'));
    }

    public function testInvalidFields(): void {
        $this->assertFalse($this->page->isFieldValid('timestamp', '2020-02-02-02-02-02'));
    }
}
