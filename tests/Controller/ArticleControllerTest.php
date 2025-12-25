<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Pagination\Paginator;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for the controllers defined inside ArticleController.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
final class ArticleControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/article/');

        $this->assertResponseIsSuccessful();

        $this->assertCount(
            5,
            $crawler->filter('article.post'),
            'The homepage displays the right number of articles.'
        );
    }

    public function testRss(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/article/rss.xml');

        $this->assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $this->assertCount(
            5,
            $crawler->filter('item'),
            'The xml file displays the right number of articles.'
        );
    }

    /**
     * This test changes the database contents by creating a new comment. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testNewComment(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneByUsername('jane_admin');

        $client->loginUser($user);

        $client->followRedirects();

        // Find first article
        $crawler = $client->request('GET', '/en/article/');
        $postLink = $crawler->filter('article.post > h2 a')->link();

        $client->click($postLink);
        $crawler = $client->submitForm('Publish comment', [
            'comment[content]' => 'Hi, Symfony!',
        ]);

        $newComment = $crawler->filter('.post-comment')->first()->filter('div > p')->text();

        $this->assertSame('Hi, Symfony!', $newComment);
    }

    public function testAjaxSearch(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/article/search', ['q' => 'lorem']);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('article.post'));
        $this->assertSame('Lorem ipsum dolor sit amet consectetur adipiscing elit', $crawler->filter('article.post')->first()->filter('h2 > a')->text());
    }
}
