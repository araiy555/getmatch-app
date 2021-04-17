<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\CommentPage;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PagerWave\CursorInterface;
use PagerWave\Extension\DoctrineOrm\QueryBuilderAdapter;
use PagerWave\PaginatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentRepository extends ServiceEntityRepository implements PrunesIpAddresses {
    use PrunesIpAddressesTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker,
        PaginatorInterface $paginator
    ) {
        parent::__construct($registry, Comment::class);

        $this->authorizationChecker = $authorizationChecker;
        $this->paginator = $paginator;
    }

    /**
     * @throws NotFoundHttpException if no such comment
     */
    public function findOneBySubmissionAndIdOr404(
        ?Submission $submission,
        ?int $id
    ): ?Comment {
        if (!$submission || !$id) {
            return null;
        }

        $comment = $this->findOneBy(['submission' => $submission, 'id' => $id]);

        if (!$comment instanceof Comment) {
            throw new NotFoundHttpException('No such comment');
        }

        return $comment;
    }

    /**
     * @return CursorInterface|Comment[]
     */
    public function findPaginated(callable $queryModifier = null): CursorInterface {
        $qb = $this->createQueryBuilder('c')
            ->where('c.visibility = :visibility')
            ->setParameter('visibility', Comment::VISIBILITY_VISIBLE);

        if ($queryModifier) {
            $queryModifier($qb);
        }

        $cursor = $this->paginator->paginate(
            new QueryBuilderAdapter($qb),
            25,
            new CommentPage(),
        );

        $this->hydrate(...$cursor);

        return $cursor;
    }

    /**
     * @return CursorInterface|Comment[]
     */
    public function findPaginatedByForum(Forum $forum): CursorInterface {
        return $this->findPaginated(static function (QueryBuilder $qb) use ($forum): void {
            $qb->join('c.submission', 's', 'WITH', 's.forum = :forum');
            $qb->setParameter('forum', $forum);
        });
    }

    /**
     * @return CursorInterface|Comment[]
     */
    public function findPaginatedByUser(User $user): CursorInterface {
        return $this->findPaginated(static function (QueryBuilder $qb) use ($user): void {
            $qb->andWhere('c.user = :user')->setParameter('user', $user);
        });
    }

    public function hydrate(Comment ...$comments): void {
        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('u')
            ->addSelect('s')
            ->addSelect('sf')
            ->addSelect('su')
            ->join('c.user', 'u')
            ->join('c.submission', 's')
            ->join('s.forum', 'sf')
            ->join('s.user', 'su')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        $this->createQueryBuilder('c')
            ->select('PARTIAL c.{id}')
            ->addSelect('cc')
            ->leftJoin('c.children', 'cc')
            ->where('c IN (?1)')
            ->setParameter(1, $comments)
            ->getQuery()
            ->execute();

        // for fast retrieval of user vote
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $this->createQueryBuilder('c')
                ->select('PARTIAL c.{id}')
                ->addSelect('cv')
                ->leftJoin('c.votes', 'cv')
                ->where('c IN (?1)')
                ->setParameter(1, $comments)
                ->getQuery()
                ->execute();
        }
    }
}
