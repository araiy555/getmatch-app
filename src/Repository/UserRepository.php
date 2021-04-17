<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Pagination\TimestampPage;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use App\SubmissionFinder\Criteria as SubmissionCriteria;
use App\SubmissionFinder\SubmissionFinder;
use App\Utils\CanonicalRedirector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;
use PagerWave\Adapter\UnionAdapter;
use PagerWave\CursorInterface;
use PagerWave\Extension\DoctrineOrm\QueryBuilderAdapter;
use PagerWave\PaginatorInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null findOneByUsername(string|string[] $username)
 * @method User|null findOneByNormalizedUsername(string|string[] $normalizedUsername)
 * @method User[]    findByUsername(string|string[] $usernames)
 * @method User[]    findByNormalizedUsername(string|string[] $usernames)
 */
class UserRepository extends ServiceEntityRepository implements PrunesIpAddresses, UserLoaderInterface {
    use PrunesIpAddressesTrait;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var CanonicalRedirector
     */
    private $canonicalizer;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    public function __construct(
        ManagerRegistry $registry,
        PaginatorInterface $paginator,
        CanonicalRedirector $canonicalizer,
        SubmissionFinder $submissionFinder
    ) {
        parent::__construct($registry, User::class);
        $this->paginator = $paginator;
        $this->canonicalizer = $canonicalizer;
        $this->submissionFinder = $submissionFinder;
    }

    /**
     * @param string|null $username
     */
    public function loadUserByUsername($username): ?User {
        if ($username === null) {
            return null;
        }

        return $this->findOneByNormalizedUsername(User::normalizeUsername($username));
    }

    public function findOneOrRedirectToCanonical(?string $username, string $param): ?User {
        $user = $this->loadUserByUsername($username);

        if ($user) {
            $this->canonicalizer->canonicalize($user->getUsername(), $param);
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function lookUpByEmail(string $email): array {
        // Normalization of email address is prone to change, so look them up
        // by both canonical and normalized variations just in case.
        return $this->createQueryBuilder('u')
            ->where('u.email = ?1')
            ->orWhere('u.normalizedEmail = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, User::normalizeEmail($email))
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->execute();
    }

    /**
     * Find the combined contributions (comments and submissions) of a user.
     *
     * This has the potential of skipping some contributions if they were posted
     * at the same second, and if they were to appear on separate pages. This is
     * an edge case, so we don't really care.
     *
     * @return CursorInterface|Submission[]|Comment[]
     */
    public function findContributions(User $user): CursorInterface {
        $submissionsQuery = $this->_em->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->andWhere('s.user = :user')
            ->andWhere('s.visibility = :visibility')
            ->setParameter('user', $user)
            ->setParameter('visibility', Submission::VISIBILITY_VISIBLE);

        $commentsQuery = $this->_em->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->andWhere('c.user = :user')
            ->andWhere('c.visibility = :visibility')
            ->setParameter('user', $user)
            ->setParameter('visibility', Comment::VISIBILITY_VISIBLE);

        $adapter = new UnionAdapter(
            new QueryBuilderAdapter($submissionsQuery),
            new QueryBuilderAdapter($commentsQuery)
        );

        $cursor = $this->paginator->paginate($adapter, 25, new TimestampPage());

        $this->hydrateContributions($cursor);

        return $cursor;
    }

    public function findTrashedContributions(User $user): CursorInterface {
        $submissionsQuery = $this->submissionFinder->getQueryBuilder(
            (new SubmissionCriteria(Submission::SORT_NEW))
                ->showUsers($user)
                ->trashed()
        );

        $commentQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->andWhere('c.user = :user')
            ->andWhere('c.visibility = :visibilty')
            ->setParameter('user', $user)
            ->setParameter('visibilty', Comment::VISIBILITY_TRASHED);

        $adapter = new UnionAdapter(
            new QueryBuilderAdapter($submissionsQuery),
            new QueryBuilderAdapter($commentQuery)
        );

        $cursor = $this->paginator->paginate($adapter, 25, new TimestampPage());

        $this->hydrateContributions($cursor);

        return $cursor;
    }

    /**
     * @return Pagerfanta|User[]
     */
    public function findPaginated(int $page, Criteria $criteria): Pagerfanta {
        $pager = new Pagerfanta(new SelectableAdapter($this, $criteria));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function findIpsUsedByUser(User $user): \Generator {
        $sql = 'SELECT DISTINCT ip FROM ('.
            'SELECT registration_ip AS ip FROM users WHERE id = :id AND registration_ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM submissions WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM comments WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM submission_votes WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM comment_votes WHERE user_id = :id AND ip IS NOT NULL UNION ALL '.
            'SELECT ip FROM messages WHERE sender_id = :id AND ip IS NOT NULL'.
        ') q';

        $sth = $this->_em->getConnection()->prepare($sql);
        $sth->bindValue(':id', $user->getId());
        $sth->execute();

        while ($ip = $sth->fetchOne()) {
            yield $ip;
        }
    }

    protected function getIpAddressField(): string {
        return 'registrationIp';
    }

    protected function getTimestampField(): string {
        return 'created';
    }

    private function hydrateContributions(iterable $contributions): void {
        $submissions = $comments = [];

        foreach ($contributions as $entity) {
            if ($entity instanceof Submission) {
                $submissions[] = $entity;
            } elseif ($entity instanceof Comment) {
                $comments[] = $entity;
            }
        }

        $this->_em->getRepository(Submission::class)->hydrate(...$submissions);
        $this->_em->getRepository(Comment::class)->hydrate(...$comments);
    }
}
