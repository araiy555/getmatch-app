<?php

namespace App\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\ForumSubscription;
use App\Entity\Moderator;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserBlock;
use App\Pagination\SubmissionPage;
use App\Repository\SiteRepository;
use App\Repository\SubmissionRepository;
use App\Security\Authentication;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PagerWave\CursorInterface;
use PagerWave\Extension\DoctrineOrm\QueryBuilderAdapter;
use PagerWave\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SubmissionFinder {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SiteRepository
     */
    private $sites;
    /**
     * @var SubmissionRepository
     */
    private $submissions;

    public function __construct(
        Authentication $authentication,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        RequestStack $requestStack,
        SiteRepository $sites,
        SubmissionRepository $submissions
    ) {
        $this->authentication = $authentication;
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
        $this->sites = $sites;
        $this->submissions = $submissions;
    }

    /**
     * Finds submissions!
     *
     * @throws NoSubmissionsException if there are no submissions
     */
    public function find(Criteria $criteria): CursorInterface {
        $qb = $this->getQueryBuilder($criteria);

        $results = $this->paginator->paginate(
            new QueryBuilderAdapter($qb),
            $criteria->getMaxPerPage(),
            new SubmissionPage($this->getSortMode($criteria->getSortBy()))
        );

        if (!$this->isOnFirstPage() && \count($results) === 0) {
            throw new NoSubmissionsException();
        }

        $this->submissions->hydrate(...$results);

        return $results;
    }

    public function getQueryBuilder(Criteria $criteria): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', $criteria->getVisibility());

        $this->addTimeClause($qb);
        $this->addStickyClause($qb, $criteria);
        $this->filter($qb, $criteria);

        return $qb;
    }

    /**
     * Get the submission ordering currently in use.
     */
    public function getSortMode(?string $sortBy): string {
        $user = $this->authentication->getUser();

        return $sortBy
            ?? ($user ? $user->getFrontPageSortMode() : null)
            ?? $this->sites->findCurrentSite()->getDefaultSortMode();
    }

    private function isOnFirstPage(): bool {
        $request = $this->requestStack->getCurrentRequest();
        \assert($request !== null);

        return !$request->query->has('next');
    }

    private function addTimeClause(QueryBuilder $qb): void {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $time = $request->query->get('t', Submission::TIME_ALL);

            if ($time !== Submission::TIME_ALL) {
                $since = new \DateTimeImmutable('@'.time());

                switch ($time) {
                case Submission::TIME_YEAR:
                    $since = $since->modify('-1 year');
                    break;
                case Submission::TIME_MONTH:
                    $since = $since->modify('-1 month');
                    break;
                case Submission::TIME_WEEK:
                    $since = $since->modify('-1 week');
                    break;
                case Submission::TIME_DAY:
                    $since = $since->modify('-1 day');
                    break;
                default:
                    // 404 on bad query parameter
                    throw new NoSubmissionsException();
                }

                $qb->andWhere('s.timestamp > :time');
                $qb->setParameter('time', $since, Types::DATETIMETZ_IMMUTABLE);
            }
        }
    }

    private function addStickyClause(QueryBuilder $qb, Criteria $criteria): void {
        if ($criteria->getStickiesFirst()) {
            if ($this->isOnFirstPage()) {
                // Order by stickies on page 1.
                $qb->addOrderBy('s.sticky', 'DESC');
            } else {
                // Exclude all stickies from page 2 and onward, since they're
                // all assumed to be on page 1. Will miss all stickies that are
                // meant to be on the next page. The solution is to not be a
                // doofus and sticky more than the max number posts per page.
                $qb->andWhere($qb->expr()->eq('s.sticky', 'false'));
            }
        }
    }

    private function filter(QueryBuilder $qb, Criteria $criteria): void {
        switch ($criteria->getView()) {
        case Criteria::VIEW_FEATURED:
            $qb->andWhere('s.forum IN (SELECT f FROM '.Forum::class.' f WHERE f.featured = TRUE)');
            break;
        case Criteria::VIEW_SUBSCRIBED:
            $qb->andWhere('s.forum IN (SELECT IDENTITY(fs.forum) FROM '.ForumSubscription::class.' fs WHERE fs.user = :user)');
            $qb->setParameter('user', $this->authentication->getUserOrThrow());
            break;
        case Criteria::VIEW_MODERATED:
            $qb->andWhere('s.forum IN (SELECT IDENTITY(m.forum) FROM '.Moderator::class.' m WHERE m.user = :user)');
            $qb->setParameter('user', $this->authentication->getUserOrThrow());
            break;
        case Criteria::VIEW_FORUMS:
            $forums = $criteria->getForums();
            if (\count($forums) > 0) {
                $qb->andWhere('s.forum IN (:forums)');
                $qb->setParameter('forums', $forums);
            } else {
                // prevent submissions showing up with an empty list of forums
                $qb->andWhere('1 = 0');
            }
            break;
        case Criteria::VIEW_USERS:
            $users = $criteria->getUsers();
            if (\count($users) > 0) {
                $qb->andWhere('s.user IN (:users)');
                $qb->setParameter('users', $users);
            } else {
                // prevent submissions showing up with an empty list of users
                $qb->andWhere('1 = 0');
            }
            break;
        case Criteria::VIEW_ALL:
            // noop
            break;
        default:
            throw new \LogicException("Bad sort mode {$criteria->getView()}");
        }

        $user = $this->authentication->getUser();

        if ($user) {
            $exclusions = $criteria->getExclusions();

            if ($exclusions & Criteria::EXCLUDE_HIDDEN_FORUMS) {
                $qb->andWhere('s.forum NOT IN (SELECT hf FROM '.User::class.' u JOIN u.hiddenForums AS hf WHERE u = :user)');
            }

            if ($exclusions & Criteria::EXCLUDE_BLOCKED_USERS) {
                $qb->andWhere('s.user NOT IN (SELECT IDENTITY(ub.blocked) FROM '.UserBlock::class.' ub WHERE ub.blocker = :user)');
            }

            if ($exclusions) {
                $qb->setParameter('user', $user);
            }
        }
    }
}
