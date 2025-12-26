<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tag;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function Symfony\Component\String\u;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for article information.
 *
 * See https://symfony.com/doc/current/doctrine.html#querying-for-objects-the-repository
 *
 * @method Article|null findOneByTitle(string $articleTitle)
 *
 * @template-extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findLatest(int $page = 1, ?Tag $tag = null, ?Category $category = null): Paginator
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('u', 't', 'c', 'i', 'v')
            ->innerJoin('a.author', 'u')
            ->leftJoin('a.tags', 't')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.images', 'i')
            ->leftJoin('a.videos', 'v')
            ->where('a.publishedAt <= :now')
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.priority', 'DESC')
            ->setParameter('now', new \DateTimeImmutable())
        ;

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF a.tags')
                ->setParameter('tag', $tag);
        }

        if (null !== $category) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $category);
        }

        return (new Paginator($qb))->paginate($page);
    }

    /**
     * @return Article[]
     */
    public function findLatestRaw(int $limit = 10, ?Tag $tag = null, ?Category $category = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('u', 't', 'c', 'i', 'v')
            ->innerJoin('a.author', 'u')
            ->leftJoin('a.tags', 't')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.images', 'i')
            ->leftJoin('a.videos', 'v')
            ->where('a.publishedAt <= :now')
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.priority', 'DESC')
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults($limit)
        ;

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF a.tags')
                ->setParameter('tag', $tag);
        }

        if (null !== $category) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $category);
        }

        // Use Doctrine Paginator to correctly handle the LIMIT when fetch-joining collections (images, tags)
        // This ensures checking "15" means "15 Articles", not "15 SQL rows"
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb, true);

        return iterator_to_array($paginator);
    }

    public function findOneTopStory(): ?Article
    {
        return $this->createQueryBuilder('a')
            ->addSelect('u', 't', 'c', 'i', 'v')
            ->innerJoin('a.author', 'u')
            ->leftJoin('a.tags', 't')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.images', 'i')
            ->leftJoin('a.videos', 'v')
            ->where('a.isTopStory = :topStory')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('topStory', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.priority', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Article[]
     */
    public function findBySearchQuery(string $query, int $limit = Paginator::PAGE_SIZE): array
    {
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === \count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('a');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('a.title LIKE :t_' . $key)
                ->setParameter('t_' . $key, '%' . $term . '%')
            ;
        }

        /** @var Article[] $result */
        $result = $queryBuilder
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.priority', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function findOneBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->addSelect('u', 't', 'c', 'i', 'v')
            ->innerJoin('a.author', 'u')
            ->leftJoin('a.tags', 't')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.images', 'i')
            ->leftJoin('a.videos', 'v')
            ->where('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Article[]
     */
    public function findTopStories(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isTopStory = :topStory')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('topStory', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.priority', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Transforms the search string into an array of search terms.
     *
     * @return string[]
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $terms = array_unique(u($searchQuery)->replaceMatches('/[[:space:]]+/', ' ')->trim()->split(' '));

        // ignore the search terms that are too short
        return array_filter($terms, static function ($term) {
            return 2 <= $term->length();
        });
    }
}

