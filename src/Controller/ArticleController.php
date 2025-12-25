<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Event\CommentCreatedEvent;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage article contents in the public part of the site.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[Route('/article')]
final class ArticleController extends AbstractController
{
    /**
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     *
     * See https://symfony.com/doc/current/routing.html#special-parameters
     */
    #[Route('/', name: 'article_index', defaults: ['page' => '1', '_format' => 'html'], methods: ['GET'])]
    #[Route('/rss.xml', name: 'article_rss', defaults: ['page' => '1', '_format' => 'xml'], methods: ['GET'])]
    #[Route('/page/{page}', name: 'article_index_paginated', defaults: ['_format' => 'html'], requirements: ['page' => Requirement::POSITIVE_INT], methods: ['GET'])]
    #[Cache(smaxage: 10)]
    public function index(Request $request, int $page, string $_format, ArticleRepository $articles, TagRepository $tags, CategoryRepository $categories): Response
    {
        $tag = null;
        $category = null;

        if ($request->query->has('tag')) {
            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        } elseif ($request->query->has('category')) {
            $category = $categories->findOneBy(['slug' => $request->query->get('category')]);
        } else {
            // Default to 'international' category on homepage if no filters applied
            $category = $categories->findOneBy(['slug' => 'international']);
        }



        $latestArticles = $articles->findLatest($page, $tag, $category);

        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templates.html#template-naming
        return $this->render('article/index.' . $_format . '.twig', [
            'paginator' => $latestArticles,
            'tagName' => $tag?->getName(),
            'categoryName' => $category?->getName(),
        ]);
    }

    #[Route('/search', name: 'article_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articles): Response
    {
        $query = (string) $request->query->get('q', '');
        $foundArticles = [];

        if ('' !== $query) {
            $foundArticles = $articles->findBySearchQuery($query);
        }

        return $this->render('article/search.html.twig', [
            'query' => $query,
            'articles' => $foundArticles,
        ]);
    }

    /**
     * NOTE: when the controller argument is a Doctrine entity, Symfony makes an
     * automatic database query to fetch it based on the value of the route parameters.
     * The '{slug:article}' configuration tells Symfony to use the 'slug' route
     * parameter in the database query that fetches the entity of the $article argument.
     * This is mostly useful when the route has multiple parameters and the controller
     * also has multiple arguments.
     * See https://symfony.com/doc/current/doctrine.html#automatically-fetching-objects-entityvalueresolver.
     */
    #[Route('/{slug:article}', name: 'article_show', requirements: ['slug' => Requirement::ASCII_SLUG], methods: ['GET'])]
    public function articleShow(Article $article): Response
    {
        return $this->render('article/show.html.twig', ['article' => $article]);
    }

    /**
     * NOTE: The #[MapEntity] mapping is required because the route parameter
     * (articleSlug) doesn't match any of the Doctrine entity properties (slug).
     *
     * See https://symfony.com/doc/current/doctrine.html#doctrine-entity-value-resolver
     */
    #[Route('/comment/{articleSlug}/new', name: 'comment_new', requirements: ['articleSlug' => Requirement::ASCII_SLUG], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function commentNew(
        #[CurrentUser] User $user,
        Request $request,
        #[MapEntity(mapping: ['articleSlug' => 'slug'])] Article $article,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
    ): Response {
        $comment = new Comment();
        $comment->setAuthor($user);
        $article->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            // When an event is dispatched, Symfony notifies it to all the listeners
            // and subscribers registered to it. Listeners can modify the information
            // passed in the event and they can even modify the execution flow, so
            // there's no guarantee that the rest of this controller will be executed.
            // See https://symfony.com/doc/current/components/event_dispatcher.html
            //
            // If you prefer to process comments asynchronously (e.g. to perform some
            // heavy tasks on them) you can use the Symfony Messenger component.
            // See https://symfony.com/doc/current/messenger.html
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

            return $this->redirectToRoute('article_show', ['slug' => $article->getSlug()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/comment_form_error.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * This controller is called directly via the render() function in the
     * article/show.html.twig template. That's why it's not needed to define
     * a route name for it.
     */
    public function commentForm(Article $article): Response
    {
        $form = $this->createForm(CommentType::class);

        return $this->render('article/_comment_form.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }


}

