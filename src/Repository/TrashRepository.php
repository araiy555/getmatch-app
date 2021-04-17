<?php

namespace App\Repository;

use App\Entity\Contracts\VisibilityInterface as Visibility;
use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\TimestampPage;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\QueryBuilder;
use PagerWave\Adapter\UnionAdapter;
use PagerWave\CursorInterface;
use PagerWave\Extension\DoctrineOrm\QueryBuilderAdapter;
use PagerWave\PaginatorInterface;

class TrashRepository {
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    /**
     * @var CommentRepository
     */
    private $commentRepository;

    public function __construct(
        PaginatorInterface $paginator,
        SubmissionFinder $submissionFinder,
        CommentRepository $commentRepository
    ) {
        $this->paginator = $paginator;
        $this->submissionFinder = $submissionFinder;
        $this->commentRepository = $commentRepository;
    }

    public function findTrash(): CursorInterface {
        $submissionCriteria = (new Criteria(Submission::SORT_NEW))->trashed();
        $commentQb = $this->commentRepository
            ->createQueryBuilder('c')
            ->where('c.visibility = :visibility')
            ->setParameter('visibility', Visibility::VISIBILITY_TRASHED);

        return $this->paginate($submissionCriteria, $commentQb);
    }

    public function findTrashForUser(User $user): CursorInterface {
        $submissionCriteria = (new Criteria(Submission::SORT_NEW))
            ->showModerated()
            ->trashed();

        $commentQb = $this->commentRepository
            ->createQueryBuilder('c')
            ->where('c.visibility = :visibility')
            ->join('c.submission', 's', 'WITH', 's.forum IN ('.
                'SELECT IDENTITY(m.forum) FROM '.Moderator::class.' m WHERE m.user = :user'.
            ')')
            ->setParameter('visibility', Visibility::VISIBILITY_TRASHED)
            ->setParameter('user', $user);

        return $this->paginate($submissionCriteria, $commentQb);
    }

    public function findTrashInForum(Forum $forum): CursorInterface {
        $submissionCriteria = (new Criteria(Submission::SORT_NEW))
            ->showForums($forum)
            ->trashed();

        $commentQb = $this->commentRepository
            ->createQueryBuilder('c')
            ->where('c.visibility = :visibility')
            ->join('c.submission', 's', 'WITH', 's.forum = :forum')
            ->setParameter('visibility', Visibility::VISIBILITY_TRASHED)
            ->setParameter('forum', $forum);

        return $this->paginate($submissionCriteria, $commentQb);
    }

    private function paginate(Criteria $submissionCriteria, QueryBuilder $commentQb): CursorInterface {
        $submissionQb = $this->submissionFinder->getQueryBuilder($submissionCriteria);

        $adapter = new UnionAdapter(
            new QueryBuilderAdapter($submissionQb),
            new QueryBuilderAdapter($commentQb)
        );

        return $this->paginator->paginate($adapter, 25, new TimestampPage());
    }
}
