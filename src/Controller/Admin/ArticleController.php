<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Security\ArticleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage article contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 * See https://symfony.com/bundles
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[Route('/admin/article')]
#[IsGranted(User::ROLE_ADMIN)]
final class ArticleController extends AbstractController
{
    /**
     * Lists all Article entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_article_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     */
    #[Route('/', name: 'admin_index', methods: ['GET'])]
    #[Route('/', name: 'admin_article_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $user,
        ArticleRepository $articles,
    ): Response {
        $authorArticles = $articles->findBy(['author' => $user], ['publishedAt' => 'DESC']);

        return $this->render('admin/article/index.html.twig', ['articles' => $authorArticles]);
    }

    /**
     * Creates a new Article entity.
     *
     * NOTE: the Method annotation is optional, but it's a recommended practice
     * to constraint the HTTP methods each controller responds to (by default
     * it responds to all methods).
     */
    #[Route('/new', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $article = new Article();
        $article->setAuthor($user);

        // See https://symfony.com/doc/current/form/multiple_buttons.html
        $form = $this->createForm(ArticleType::class, $article)
            ->add('saveAndCreateNew', SubmitType::class)
        ;

        $form->handleRequest($request);

        // The isSubmitted() call is mandatory because the isValid() method
        // throws an exception if the form has not been submitted.
        // See https://symfony.com/doc/current/forms.html#processing-forms
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            // See https://symfony.com/doc/current/controller.html#flash-messages
            $this->addFlash('success', 'article.created_successfully');

            /** @var SubmitButton $submit */
            $submit = $form->get('saveAndCreateNew');

            if ($submit->isClicked()) {
                return $this->redirectToRoute('admin_article_new', [], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Finds and displays an Article entity.
     */
    #[Route('/{id:article}', name: 'admin_article_show', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    public function show(Article $article): Response
    {
        // This security check can also be performed
        // using a PHP attribute: #[IsGranted('show', subject: 'article', message: 'Articles can only be shown to their authors.')]
        $this->denyAccessUnlessGranted(ArticleVoter::SHOW, $article, 'Articles can only be shown to their authors.');

        return $this->render('admin/article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Displays a form to edit an existing Article entity.
     */
    #[Route('/{id:article}/edit', name: 'admin_article_edit', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET', 'POST'])]
    #[IsGranted('edit', subject: 'article', message: 'Articles can only be edited by their authors.')]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'article.updated_successfully');

            return $this->redirectToRoute('admin_article_edit', ['id' => $article->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    /**
     * Deletes an Article entity.
     */
    #[Route('/{id:article}/delete', name: 'admin_article_delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['POST'])]
    #[IsGranted('delete', subject: 'article')]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        /** @var string|null $token */
        $token = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('delete', $token)) {
            return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
        }

        // Delete the tags associated with this article. This is done automatically
        // by Doctrine, except for SQLite (the database used in this application)
        // because foreign key support is not enabled by default in SQLite
        $article->getTags()->clear();

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'article.deleted_successfully');

        return $this->redirectToRoute('admin_article_index', [], Response::HTTP_SEE_OTHER);
    }
}

