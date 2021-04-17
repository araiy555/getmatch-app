<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\ForumLogCommentDeletion;
use App\Entity\ForumLogSubmissionDeletion;
use App\Entity\Moderator;
use App\Entity\Submission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class ModeratorRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Moderator::class);
    }

    /**
     * Returns true if any of the following conditions are true.
     *
     * - `$subject` moderates a forum together with `$moderator`.
     *
     * - `$subject` has posted a submission or a comment in a forum `$moderator`
     *   moderates within the past 3 days.
     *
     * - `$subject` has had a submission or a comment removed from a forum
     *   `$moderator` moderates within the past 2 hours.
     */
    public function userRulesOverSubject(User $moderator, User $subject): bool {
        $threshold1 = new \DateTimeImmutable('@'.time().' -3 days');
        $threshold2 = new \DateTimeImmutable('@'.time().' -2 hours');

        $qb = $this->createQueryBuilder('m1');

        return $qb->select('COUNT(m1)')
            ->where('m1.user = :moderator')
            ->andWhere($qb->expr()->orX(
                'm1.forum IN ('.
                    'SELECT IDENTITY(m2.forum) FROM '.Moderator::class.' m2 '.
                    'WHERE m2.user = :subject'.
                ')',
                'm1.forum IN ('.
                    'SELECT IDENTITY(s1.forum) FROM '.Submission::class.' s1 '.
                    'WHERE s1.timestamp >= :threshold1 AND s1.user = :subject'.
                ')',
                'm1.forum IN ('.
                    'SELECT IDENTITY(s2.forum) FROM '.Comment::class.' c '.
                    'JOIN c.submission s2 '.
                    'WHERE c.timestamp >= :threshold1 AND c.user = :subject'.
                ')',
                'm1.forum IN ('.
                    'SELECT IDENTITY(sd.forum) FROM '.ForumLogSubmissionDeletion::class.' sd '.
                    'WHERE sd.timestamp >= :threshold2 AND sd.author = :subject'.
                ')',
                'm1.forum IN ('.
                    'SELECT IDENTITY(cd.forum) FROM '.ForumLogCommentDeletion::class.' cd '.
                    'WHERE cd.timestamp >= :threshold2 AND cd.author = :subject'.
                ')'
            ))
            ->setParameter('moderator', $moderator)
            ->setParameter('subject', $subject)
            ->setParameter('threshold1', $threshold1, Types::DATETIMETZ_IMMUTABLE)
            ->setParameter('threshold2', $threshold2, Types::DATETIMETZ_IMMUTABLE)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
