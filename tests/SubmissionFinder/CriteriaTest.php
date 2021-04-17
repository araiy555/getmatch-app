<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Submission;
use App\SubmissionFinder\Criteria;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\SubmissionFinder\Criteria
 */
class CriteriaTest extends TestCase {
    public function testDefaults(): void {
        $criteria = new Criteria(Submission::SORT_HOT);

        $this->assertSame(Submission::SORT_HOT, $criteria->getSortBy());
        $this->assertSame(Criteria::VIEW_ALL, $criteria->getView());
        $this->assertSame(0, $criteria->getExclusions());
        $this->assertFalse($criteria->getStickiesFirst());
        $this->assertSame(25, $criteria->getMaxPerPage());
    }

    /**
     * @doesNotPerformAssertions
     * @dataProvider provideSortModes
     */
    public function testAcceptsValidSortModes(?string $sortMode): void {
        new Criteria($sortMode);
    }

    /**
     * @dataProvider provideExcludeMethods
     */
    public function testExcludeHiddenForums(string $method, int $flag): void {
        $criteria = $this->createCriteria();
        $criteria->$method();

        $this->assertSame($flag, $criteria->getExclusions() & $flag);
    }

    /**
     * @dataProvider provideExcludeMethods
     */
    public function testExcludeMethodsCannotBeCalledTwice(string $method): void {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('This method was already called');

        $this->createCriteria()->$method()->$method();
    }

    public function testExcludeMethodsCanBeCombined(): void {
        $criteria = $this->createCriteria();
        $flags = 0;
        foreach ($this->provideExcludeMethods() as [$method, $flag]) {
            $criteria->$method();
            $flags += $flag;
        }

        $this->assertSame($flags, $criteria->getExclusions());
    }

    public function testExcludeHiddenForumsCannotBeCalledTwice(): void {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('This method was already called');

        $this->createCriteria()
            ->excludeHiddenForums()
            ->excludeHiddenForums();
    }

    public function testStickies(): void {
        $criteria = $this->createCriteria()->stickiesFirst();

        $this->assertTrue($criteria->getStickiesFirst());
    }

    public function testFeaturedView(): void {
        $criteria = $this->createCriteria()->showFeatured();

        $this->assertSame(Criteria::VIEW_FEATURED, $criteria->getView());
    }

    public function testForumView(): void {
        $forum1 = EntityFactory::makeForum();
        $forum2 = EntityFactory::makeForum();

        $criteria = $this->createCriteria()->showForums($forum1, $forum2);

        $this->assertSame(Criteria::VIEW_FORUMS, $criteria->getView());
        $this->assertSame([$forum1, $forum2], $criteria->getForums());
    }

    public function testMaxPerPage(): void {
        $criteria = $this->createCriteria()->setMaxPerPage(69);

        $this->assertSame(69, $criteria->getMaxPerPage());
    }

    public function testModeratedView(): void {
        $criteria = $this->createCriteria()->showModerated();

        $this->assertSame(Criteria::VIEW_MODERATED, $criteria->getView());
    }

    /**
     * @dataProvider provideViewMethodMatrix
     */
    public function testNoViewMethodCanBeCalledAfterAnother(string $first, string $second): void {
        $this->expectException(\BadMethodCallException::class);

        $this->createCriteria()->$first()->$second();
    }

    public function testSubscribedView(): void {
        $criteria = $this->createCriteria();
        $criteria->showSubscribed();

        $this->assertSame(Criteria::VIEW_SUBSCRIBED, $criteria->getView());
    }

    public function testUserView(): void {
        $user1 = EntityFactory::makeUser();
        $user2 = EntityFactory::makeUser();

        $criteria = new Criteria(Submission::SORT_HOT);
        $criteria->showUsers($user1, $user2);

        $this->assertSame(Criteria::VIEW_USERS, $criteria->getView());
        $this->assertSame([$user1, $user2], $criteria->getUsers());
    }

    /**
     * @dataProvider provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled
     */
    public function testCannotCallGetterMethodsWithoutHavingCalledTheirRespectiveMutators(string $getter): void {
        $this->expectException(\BadMethodCallException::class);

        $this->createCriteria()->$getter();
    }

    public function testThrowsOnInvalidSortMode(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown sort mode 'poop");

        new Criteria('poop');
    }

    public function testDefaultVisibilityIsVisible(): void {
        $this->assertSame(
            Submission::VISIBILITY_VISIBLE,
            $this->createCriteria()->getVisibility()
        );
    }

    public function testCanSetTrashedVisibility(): void {
        $this->assertSame(
            Submission::VISIBILITY_TRASHED,
            $this->createCriteria()->trashed()->getVisibility()
        );
    }

    public function testCannotSetTrashedVisibilityMultipleTimes(): void {
        $this->expectException(\BadMethodCallException::class);

        $this->createCriteria()->trashed()->trashed();
    }

    public function provideExcludeMethods(): iterable {
        yield ['excludeHiddenForums', Criteria::EXCLUDE_HIDDEN_FORUMS];
        yield ['excludeBlockedUsers', Criteria::EXCLUDE_BLOCKED_USERS];
    }

    public function provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled(): iterable {
        yield ['getForums'];
        yield ['getUsers'];
    }

    public function provideSortModes(): iterable {
        yield from array_map(static function ($mode) {
            return [$mode];
        }, Submission::SORT_OPTIONS);
        yield [null];
    }

    public function provideViewMethodMatrix(): iterable {
        $viewMethods = [
            'showFeatured',
            'showSubscribed',
            'showModerated',
            'showForums',
            'showUsers',
        ];

        foreach ($viewMethods as $y) {
            foreach ($viewMethods as $x) {
                yield [$y, $x];
            }
        }
    }

    private function createCriteria(): Criteria {
        return new Criteria(Submission::SORT_HOT);
    }
}
