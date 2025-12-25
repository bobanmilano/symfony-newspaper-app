<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;

use function Symfony\Component\String\u;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadCategories($manager);
        $this->loadTags($manager);
        $this->loadArticles($manager);
    }

    private function loadUsers(ObjectManager $manager): void
    {
        foreach ($this->getUserData() as [$fullname, $username, $password, $email, $roles]) {
            $user = new User();
            $user->setFullName($fullname);
            $user->setUsername($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($email);
            $user->setRoles($roles);

            $manager->persist($user);

            $this->addReference($username, $user);
        }

        $manager->flush();
    }

    private function loadCategories(ObjectManager $manager): void
    {
        foreach ($this->getCategoryData() as [$name, $slug, $description, $color]) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($slug);
            $category->setDescription($description);
            $category->setColor($color);

            $manager->persist($category);

            $this->addReference('category-'.$slug, $category);
        }

        $manager->flush();
    }

    private function loadTags(ObjectManager $manager): void
    {
        foreach ($this->getTagData() as $name) {
            $tag = new Tag($name);

            $manager->persist($tag);

            $this->addReference('tag-'.$name, $tag);
        }

        $manager->flush();
    }

    private function loadArticles(ObjectManager $manager): void
    {
        foreach ($this->getArticleData() as [$title, $slug, $summary, $lead, $content, $publishedAt, $author, $category, $tags, $priority, $isTopStory]) {
            $article = new Article();
            $article->setTitle($title);
            $article->setSlug($slug);
            $article->setSummary($summary);
            $article->setLead($lead);
            $article->setContent($content);
            $article->setPublishedAt($publishedAt);
            $article->setAuthor($author);
            $article->setCategory($category);
            $article->setPriority($priority);
            $article->setIsTopStory($isTopStory);
            $article->addTag(...$tags);

            foreach (range(1, 5) as $i) {
                $comment = new Comment();
                $comment->setAuthor($this->getReference('john_user', User::class));
                $comment->setContent($this->getRandomText(random_int(255, 512)));
                $comment->setPublishedAt(new \DateTimeImmutable('now + '.$i.'seconds'));

                $article->addComment($comment);
            }

            $manager->persist($article);
        }

        $manager->flush();
    }

    /**
     * @return array<array{string, string, string, string, array<string>}>
     */
    private function getUserData(): array
    {
        return [
            // $userData = [$fullname, $username, $password, $email, $roles];
            ['Jane Doe', 'jane_admin', 'kitten', 'jane_admin@symfony.com', [User::ROLE_ADMIN]],
            ['Tom Doe', 'tom_admin', 'kitten', 'tom_admin@symfony.com', [User::ROLE_ADMIN]],
            ['John Doe', 'john_user', 'kitten', 'john_user@symfony.com', [User::ROLE_USER]],
        ];
    }

    /**
     * @return string[]
     */
    private function getTagData(): array
    {
        return [
            'lorem',
            'ipsum',
            'consectetur',
            'adipiscing',
            'incididunt',
            'labore',
            'voluptate',
            'dolore',
            'pariatur',
        ];
    }

    /**
     * @return array<array{string, string, string|null, string|null}>
     */
    private function getCategoryData(): array
    {
        return [
            // [$name, $slug, $description, $color]
            ['International', 'international', 'Internationale Nachrichten und Ereignisse', '#1f77b4'],
            ['Inland', 'inland', 'Nachrichten aus dem Inland', '#ff7f0e'],
            ['Wirtschaft', 'wirtschaft', 'Wirtschafts- und Finanznachrichten', '#2ca02c'],
            ['Web', 'web', 'Internet, Technologie und Digitales', '#d62728'],
            ['Sport', 'sport', 'Sportnachrichten und Ergebnisse', '#9467bd'],
            ['Kultur', 'kultur', 'Kultur, Kunst und Unterhaltung', '#8c564b'],
            ['Wissenschaft', 'wissenschaft', 'Wissenschaft und Forschung', '#e377c2'],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: AbstractUnicodeString, 2: string, 3: string|null, 4: string, 5: \DateTimeImmutable, 6: User, 7: Category, 8: array<Tag>, 9: int, 10: bool}>
     *
     * @throws \Exception
     */
    private function getArticleData(): array
    {
        $articles = [];
        $categorySlugs = ['international', 'inland', 'wirtschaft', 'web', 'sport', 'kultur', 'wissenschaft'];

        foreach ($this->getPhrases() as $i => $title) {
            // $articleData = [$title, $slug, $summary, $lead, $content, $publishedAt, $author, $category, $tags, $priority, $isTopStory];
            $articles[] = [
                $title,
                $this->slugger->slug($title)->lower(),
                $this->getRandomText(),
                $i < 3 ? $this->getRandomText(150) : null, // First 3 articles have a lead
                $this->getArticleContent(),
                (new \DateTimeImmutable('now - '.$i.'days'))->setTime(random_int(8, 17), random_int(7, 49), random_int(0, 59)),
                // Ensure that the first article is written by Jane Doe to simplify tests
                $this->getReference(['jane_admin', 'tom_admin'][0 === $i ? 0 : random_int(0, 1)], User::class),
                $this->getReference('category-'.$categorySlugs[$i % \count($categorySlugs)], Category::class),
                $this->getRandomTags(),
                $i < 5 ? random_int(50, 100) : random_int(0, 50), // First 5 articles have higher priority
                $i < 2, // First 2 articles are top stories
            ];
        }

        return $articles;
    }

    /**
     * @return string[]
     */
    private function getPhrases(): array
    {
        return [
            'Lorem ipsum dolor sit amet consectetur adipiscing elit',
            'Pellentesque vitae velit ex',
            'Mauris dapibus risus quis suscipit vulputate',
            'Eros diam egestas libero eu vulputate risus',
            'In hac habitasse platea dictumst',
            'Morbi tempus commodo mattis',
            'Ut suscipit posuere justo at vulputate',
            'Ut eleifend mauris et risus ultrices egestas',
            'Aliquam sodales odio id eleifend tristique',
            'Urna nisl sollicitudin id varius orci quam id turpis',
            'Nulla porta lobortis ligula vel egestas',
            'Curabitur aliquam euismod dolor non ornare',
            'Sed varius a risus eget aliquam',
            'Nunc viverra elit ac laoreet suscipit',
            'Pellentesque et sapien pulvinar consectetur',
            'Ubi est barbatus nix',
            'Abnobas sunt hilotaes de placidus vita',
            'Ubi est audax amicitia',
            'Eposs sunt solems de superbus fortis',
            'Vae humani generis',
            'Diatrias tolerare tanquam noster caesium',
            'Teres talis saepe tractare de camerarius flavum sensorem',
            'Silva de secundus galatae demitto quadra',
            'Sunt accentores vitare salvus flavum parses',
            'Potus sensim ad ferox abnoba',
            'Sunt seculaes transferre talis camerarius fluctuies',
            'Era brevis ratione est',
            'Sunt torquises imitari velox mirabilis medicinaes',
            'Mineralis persuadere omnes finises desiderium',
            'Bassus fatalis classiss virtualiter transferre de flavum',
        ];
    }

    private function getRandomText(int $maxLength = 255): string
    {
        $phrases = $this->getPhrases();
        shuffle($phrases);

        do {
            $text = u('. ')->join($phrases)->append('.');
            array_pop($phrases);
        } while ($text->length() > $maxLength);

        return $text;
    }

    private function getArticleContent(): string
    {
        return <<<'MARKDOWN'
            Lorem ipsum dolor sit amet consectetur adipisicing elit, sed do eiusmod tempor
            incididunt ut labore et **dolore magna aliqua**: Duis aute irure dolor in
            reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
            Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
            deserunt mollit anim id est laborum.

              * Ut enim ad minim veniam
              * Quis nostrud exercitation *ullamco laboris*
              * Nisi ut aliquip ex ea commodo consequat

            Praesent id fermentum lorem. Ut est lorem, fringilla at accumsan nec, euismod at
            nunc. Aenean mattis sollicitudin mattis. Nullam pulvinar vestibulum bibendum.
            Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos
            himenaeos. Fusce nulla purus, gravida ac interdum ut, blandit eget ex. Duis a
            luctus dolor.

            Integer auctor massa maximus nulla scelerisque accumsan. *Aliquam ac malesuada*
            ex. Pellentesque tortor magna, vulputate eu vulputate ut, venenatis ac lectus.
            Praesent ut lacinia sem. Mauris a lectus eget felis mollis feugiat. Quisque
            efficitur, mi ut semper pulvinar, urna urna blandit massa, eget tincidunt augue
            nulla vitae est.

            Ut posuere aliquet tincidunt. Aliquam erat volutpat. **Class aptent taciti**
            sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Morbi
            arcu orci, gravida eget aliquam eu, suscipit et ante. Morbi vulputate metus vel
            ipsum finibus, ut dapibus massa feugiat. Vestibulum vel lobortis libero. Sed
            tincidunt tellus et viverra scelerisque. Pellentesque tincidunt cursus felis.
            Sed in egestas erat.

            Aliquam pulvinar interdum massa, vel ullamcorper ante consectetur eu. Vestibulum
            lacinia ac enim vel placerat. Integer pulvinar magna nec dui malesuada, nec
            congue nisl dictum. Donec mollis nisl tortor, at congue erat consequat a. Nam
            tempus elit porta, blandit elit vel, viverra lorem. Sed sit amet tellus
            tincidunt, faucibus nisl in, aliquet libero.
            MARKDOWN;
    }

    /**
     * @return array<Tag>
     *
     * @throws \Exception
     */
    private function getRandomTags(): array
    {
        $tagNames = $this->getTagData();
        shuffle($tagNames);
        $selectedTags = \array_slice($tagNames, 0, random_int(2, 4));

        return array_map(
            fn ($tagName) => $this->getReference('tag-'.$tagName, Tag::class),
            $selectedTags
        );
    }
}
