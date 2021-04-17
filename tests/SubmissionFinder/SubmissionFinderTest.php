<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Repository\ForumRepository;
use App\Repository\UserRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\NoSubmissionsException;
use App\SubmissionFinder\SubmissionFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\SubmissionFinder\SubmissionFinder
 */
class SubmissionFinderTest extends KernelTestCase {
    /**
     * @var Request
     */
    private $request;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    protected function setUp(): void {
        self::bootKernel();

        $this->request = Request::create('/');
        self::$container->get(RequestStack::class)->push($this->request);

        $this->submissionFinder = self::$container->get(SubmissionFinder::class);
    }

    public function testQueryWithEmptyResultsThrowsNotFoundException(): void {
        $this->expectException(NoSubmissionsException::class);

        $this->request->query->set('next', ['id' => 0]);

        $this->submissionFinder->find((new Criteria(Submission::SORT_NEW)));
    }

    public function testInvalidTimeFilterThrowsNotFoundException(): void {
        $this->expectException(NoSubmissionsException::class);

        $this->request->query->set('t', 'invalid');

        $this->submissionFinder->find(new Criteria(Submission::SORT_NEW));
    }

    public function provideTimeFilters(): \Generator {
        yield [Submission::TIME_ALL, 5];
        yield [Submission::TIME_YEAR, 4];
        yield [Submission::TIME_MONTH, 3];
        yield [Submission::TIME_WEEK, 2];
        yield [Submission::TIME_DAY, 1];
    }

    public function testShowForums(): void {
        /** @var Forum $forum */
        $forum = self::$container->get(ForumRepository::class)
            ->findOneByName('cats');

        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showForums($forum);

        $submissions = $this->submissionFinder->find($criteria);

        $this->assertSame(['cats'], array_unique(array_map(function ($submission) {
            return $submission->getForum()->getName();
        }, iterator_to_array($submissions))));
    }

    public function testEmptyShowForums(): void {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showForums();

        $this->assertEmpty($this->submissionFinder->find($criteria));
    }

    public function testShowUsers(): void {
        /** @var User $user */
        $user = self::$container->get(UserRepository::class)
            ->loadUserByUsername('emma');

        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers($user);

        $submissions = $this->submissionFinder->find($criteria);

        $this->assertSame(['emma'], array_unique(array_map(function ($submission) {
            return $submission->getUser()->getUsername();
        }, iterator_to_array($submissions))));
    }

    public function testEmptyShowUsers(): void {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers();

        $this->assertEmpty($this->submissionFinder->find($criteria));
    }
}
