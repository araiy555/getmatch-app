<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Submission;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Request;

class SearchRepository {
    private const MAX_PER_PAGE = 50;

    private const ENTITY_TYPES = [
        Comment::class => 'comment',
        Submission::class => 'submission',
    ];

    private const ENTITY_HEADLINES = [
        Comment::class => [
            'body_excerpt' => [
                'document' => 'e.body',
                'config' => 'MaxFragments=3',
            ],
        ],
        Submission::class => [
            'title_highlighted' => [
                'document' => 'e.title',
                'config' => 'HighlightAll=TRUE',
            ],
            'body_excerpt' => [
                'document' => 'e.body',
                'config' => 'MaxFragments=3',
            ],
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * The more amazing-er cross-entity search engine.
     *
     * @param array $options An array with the following options:
     *                       - `query` (string)
     *                       - `is` (array with entity class names)
     *
     * @todo pagination, more options!
     */
    public function search(array $options): array {
        $results = [];

        foreach ($options['is'] as $entityClass) {
            foreach ($this->getResultsForEntity($options, $entityClass) as $row) {
                $results[] = $row;
            }
        }

        usort($results, static function ($a, $b) {
            return $b['search_rank'] <=> $a['search_rank'];
        });

        return \array_slice($results, 0, self::MAX_PER_PAGE);
    }

    public static function parseRequest(Request $request): ?array {
        $query = $request->query->get('q');

        if (!\is_string($query)) {
            return null;
        }

        $options = [
            'is' => [Comment::class, Submission::class],
            'query' => $query,
        ];

        return $options;
    }

    private function getResultsForEntity(array $options, string $entityClass): iterable {
        if (!isset(self::ENTITY_TYPES[$entityClass])) {
            throw new \InvalidArgumentException(sprintf(
                'non-searchable entity "%s"',
                $entityClass
            ));
        }

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($entityClass, 'e');
        $rsm->addScalarResult('entity', 'entity');
        $rsm->addScalarResult('search_rank', 'search_rank');

        $qb = $this->em->getConnection()->createQueryBuilder();

        foreach (self::ENTITY_HEADLINES[$entityClass] as $name => $headline) {
            $rsm->addScalarResult($name, $name);

            $qb->addSelect(sprintf(
                "ts_headline(%s, search_query, :{$name}_config) AS %s",
                $headline['document'],
                $name
            ))->setParameter("{$name}_config", $headline['config'] ?? '');
        }

        $table = $this->em->getClassMetadata($entityClass)->getTableName();

        $qb
            ->addSelect($rsm->generateSelectClause())
            ->addSelect(':entity::TEXT AS entity')
            ->addSelect('ts_rank(search_doc, search_query) AS search_rank')
            ->from($table, 'e')
            ->from('plainto_tsquery(:query::TEXT)', 'search_query')
            ->where('search_doc @@ search_query')
            ->setParameter('entity', self::ENTITY_TYPES[$entityClass], Types::TEXT)
            ->setParameter('query', $options['query'], Types::TEXT)
            ->orderBy('search_rank', 'DESC')
            ->setMaxResults(self::MAX_PER_PAGE);

        $nativeQuery = $this->em->createNativeQuery($qb->getSQL(), $rsm);

        foreach ($qb->getParameters() as $key => $value) {
            $nativeQuery->setParameter($key, $value, $qb->getParameterType($key));
        }

        return $nativeQuery->execute();
    }
}
