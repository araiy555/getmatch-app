<?php

/** @noinspection SqlDialectInspection */

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\ForumSubscription;
use App\Entity\Moderator;
use App\Entity\User;
use App\Utils\CanonicalRedirector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @method Forum|null find(int $id)
 * @method Forum[]    findByNormalizedName(string|string[] $normalizedName)
 * @method Forum|null findOneByNormalizedName(string $normalizedName)
 */
class ForumRepository extends ServiceEntityRepository {
    /**
     * @var CanonicalRedirector
     */
    private $canonicalizer;

    public function __construct(
        ManagerRegistry $registry,
        CanonicalRedirector $canonicalizer
    ) {
        parent::__construct($registry, Forum::class);

        $this->canonicalizer = $canonicalizer;
    }

    /**
     * @param string $sortBy one of 'name', 'title', 'submissions',
     *                       'subscribers', or 'creation_date', optionally with
     *                       'by_' prefix
     *
     * @return Pagerfanta|Forum[]
     */
    public function findForumsByPage(int $page, string $sortBy): Pagerfanta {
        $qb = $this->createQueryBuilder('f');

        switch (preg_replace('/^by_/', '', $sortBy)) {
        case 'name':
            break;
        case 'title':
            $qb->orderBy('LOWER(f.title)', 'ASC');
            break;
        case 'submissions':
            $qb->addSelect('COUNT(s) AS HIDDEN submissions')
                ->leftJoin('f.submissions', 's')
                ->orderBy('submissions', 'DESC');
            break;
        case 'subscribers':
            $qb->addSelect('COUNT(s) AS HIDDEN subscribers')
                ->leftJoin('f.subscriptions', 's')
                ->orderBy('subscribers', 'DESC');
            break;
        case 'creation_date':
            $qb->orderBy('f.created', 'DESC');
            break;
        default:
            throw new \InvalidArgumentException("invalid sort type '$sortBy'");
        }

        $qb->addOrderBy('f.normalizedName', 'ASC')->groupBy('f.id');

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    /**
     * @return string[]
     */
    public function findSubscribedForumNames(User $user): array {
        $dql =
            'SELECT f.id, f.name FROM '.Forum::class.' f WHERE f IN ('.
                'SELECT IDENTITY(fs.forum) FROM '.ForumSubscription::class.' fs WHERE fs.user = ?1'.
            ') ORDER BY f.normalizedName ASC';

        $names = $this->getEntityManager()->createQuery($dql)
            ->setParameter(1, $user)
            ->getResult();

        return array_column($names, 'name', 'id');
    }

    /**
     * Get the names of the featured forums.
     *
     * @return string[]
     */
    public function findFeaturedForumNames(): array {
        $names = $this->createQueryBuilder('f')
            ->select('f.id')
            ->addSelect('f.name')
            ->where('f.featured = TRUE')
            ->orderBy('f.normalizedName', 'ASC')
            ->getQuery()
            ->execute();

        return array_column($names, 'name', 'id');
    }

    /**
     * @return string[]
     */
    public function findModeratedForumNames(User $user): array {
        $dql = 'SELECT f.id, f.name FROM '.Forum::class.' f WHERE f IN ('.
            'SELECT IDENTITY(m.forum) FROM '.Moderator::class.' m WHERE m.user = ?1'.
        ') ORDER BY f.normalizedName ASC';

        $names = $this->getEntityManager()->createQuery($dql)
            ->setParameter(1, $user)
            ->getResult();

        return array_column($names, 'name', 'id');
    }

    public function findAllForumNames(): array {
        $dql = 'SELECT f.id, f.name FROM '.Forum::class.' f ORDER BY f.normalizedName ASC';

        $names = $this->getEntityManager()->createQuery($dql)->getResult();

        return array_column($names, 'name', 'id');
    }

    public function findOneByCaseInsensitiveName(?string $name): ?Forum {
        if ($name === null) {
            // for the benefit of param converters which for some reason insist
            // on calling repository methods with null parameters.
            return null;
        }

        return $this->findOneByNormalizedName(Forum::normalizeName($name));
    }

    /**
     * @param string $param name of forum name param in request attribute bag
     *
     * @throws HttpException when redirecting to canonical URL
     */
    public function findOneOrRedirectToCanonical(?string $name, string $param): ?Forum {
        $forum = $this->findOneByCaseInsensitiveName($name);

        if ($forum) {
            $this->canonicalizer->canonicalize($forum->getName(), $param);
        }

        return $forum;
    }
}
