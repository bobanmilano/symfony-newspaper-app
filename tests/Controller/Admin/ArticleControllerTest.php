<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the ArticleController used
 * for managing the articles in the backend.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * https://symfony.com/doc/current/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class ArticleControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneByUsername('jane_admin');
        $this->client->loginUser($user);
    }

    #[DataProvider('getUrlsForRegularUsers')]
    public function testAccessDeniedForRegularUsers(string $httpMethod, string $url): void
    {
        $this->client->getCookieJar()->clear();

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneByUsername('john_user');
        $this->client->loginUser($user);

        $this->client->request($httpMethod, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public static function getUrlsForRegularUsers(): \Generator
    {
        yield ['GET', '/en/admin/article/'];
        yield ['GET', '/en/admin/article/1'];
        yield ['GET', '/en/admin/article/1/edit'];
        yield ['POST', '/en/admin/article/1/delete'];
    }

    public function testAdminBackendHomePage(): void
    {
        $this->client->request('GET', '/en/admin/article/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            'body#admin_article_index #main tbody tr',
            'The backend homepage displays all the available articles.'
        );
    }

    /**
     * This test changes the database contents by creating a new article. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminNewArticle(): void
    {
        $articleTitle = 'Article Title ' . mt_rand();
        $articleSummary = $this->generateRandomString(255);
        $articleContent = $this->generateRandomString(1024);

        $this->client->request('GET', '/en/admin/article/new');
        $this->client->submitForm('Create article', [
            'article[title]' => $articleTitle,
            'article[summary]' => $articleSummary,
            'article[content]' => $articleContent,
        ]);

        $this->assertResponseRedirects('/en/admin/article/', Response::HTTP_SEE_OTHER);

        /** @var ArticleRepository $articleRepository */
        $articleRepository = static::getContainer()->get(ArticleRepository::class);

        $article = $articleRepository->findOneByTitle($articleTitle);

        $this->assertNotNull($article);
        $this->assertSame($articleSummary, $article->getSummary());
        $this->assertSame($articleContent, $article->getContent());
    }

    public function testAdminNewDuplicatedArticle(): void
    {
        $articleTitle = 'Article Title ' . mt_rand();
        $articleSummary = $this->generateRandomString(255);
        $articleContent = $this->generateRandomString(1024);

        $crawler = $this->client->request('GET', '/en/admin/article/new');
        $form = $crawler->selectButton('Create article')->form([
            'article[title]' => $articleTitle,
            'article[summary]' => $articleSummary,
            'article[content]' => $articleContent,
        ]);
        $this->client->submit($form);

        // article titles must be unique, so trying to create the same article twice should result in an error
        $this->client->submit($form);

        $this->assertSelectorExists('form #article_title.is-invalid');
        $this->assertSelectorTextContains(
            'form .invalid-feedback',
            'This title was already used in another article, but they must be unique.'
        );
    }

    public function testAdminShowArticle(): void
    {
        $this->client->request('GET', '/en/admin/article/1');

        $this->assertResponseIsSuccessful();
    }

    /**
     * This test changes the database contents by editing an article. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminEditArticle(): void
    {
        $newArticleTitle = 'Article Title ' . mt_rand();

        $this->client->request('GET', '/en/admin/article/1/edit');
        $this->client->submitForm('Save changes', [
            'article[title]' => $newArticleTitle,
        ]);

        $this->assertResponseRedirects('/en/admin/article/1/edit', Response::HTTP_SEE_OTHER);

        /** @var ArticleRepository $articleRepository */
        $articleRepository = static::getContainer()->get(ArticleRepository::class);

        /** @var Article $article */
        $article = $articleRepository->find(1);

        $this->assertSame($newArticleTitle, $article->getTitle());
    }

    /**
     * This test changes the database contents by deleting an article. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminDeleteArticle(): void
    {
        $crawler = $this->client->request('GET', '/en/admin/article/1');
        $this->client->submit($crawler->filter('#delete-form')->form());

        $this->assertResponseRedirects('/en/admin/article/', Response::HTTP_SEE_OTHER);

        /** @var ArticleRepository $articleRepository */
        $articleRepository = static::getContainer()->get(ArticleRepository::class);

        $this->assertNull($articleRepository->find(1));
    }

    private function generateRandomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return mb_substr(str_shuffle(str_repeat($chars, (int) ceil($length / mb_strlen($chars)))), 1, $length);
    }
}
