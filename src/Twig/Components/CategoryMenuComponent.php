<?php

namespace App\Twig\Components;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('category_menu')]
final class CategoryMenuComponent
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private \Symfony\Contracts\Cache\CacheInterface $cache
    ) {
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        return $this->cache->get('category_menu_items', function () {
            return $this->categoryRepository->findAllOrderedByName();
        });
    }
}
