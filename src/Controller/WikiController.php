<?php

namespace App\Controller;

use App\Entity\WikiPage;
use App\Entity\WikiRevision;
use App\Form\Model\WikiData;
use App\Form\WikiType;
use App\Repository\WikiPageRepository;
use App\Repository\WikiRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("view_wiki", statusCode=404)
 */
final class WikiController extends AbstractController {
    /**
     * Views a wiki page.
     */
    public function wiki(string $path, WikiPageRepository $wikiPageRepository): Response {
        $page = $wikiPageRepository->findOneCaseInsensitively($path);

        if (!$page) {
            return $this->render('wiki/404.html.twig', [
                'path' => $path,
            ], new Response('', 404));
        }

        return $this->render('wiki/page.html.twig', [
            'page' => $page,
        ]);
    }

    /**
     * Creates a wiki page.
     *
     * @IsGranted("ROLE_USER")
     *
     * @todo handle conflicts
     * @todo do something if the page already exists
     */
    public function create(Request $request, string $path, EntityManagerInterface $em): Response {
        $data = new WikiData();

        $form = $this->createForm(WikiType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page = $data->toPage($path, $this->getUser());

            $em->persist($page);
            $em->flush();

            return $this->redirectToRoute('wiki', ['path' => $path]);
        }

        return $this->render('wiki/create.html.twig', [
            'form' => $form->createView(),
            'path' => $path,
        ]);
    }

    /**
     * @Entity("page", expr="repository.findOneCaseInsensitively(path)")
     * @IsGranted("ROLE_USER")
     * @IsGranted("delete", subject="page", statusCode=403)
     */
    public function delete(Request $request, WikiPage $page, EntityManagerInterface $em): Response {
        $this->validateCsrf('wiki_delete', $request->request->get('token'));

        $em->remove($page);
        $em->flush();

        $this->addFlash('success', 'flash.wiki_page_deleted');

        return $this->redirectToRoute('wiki');
    }

    /**
     * Edits a wiki page.
     *
     * @Entity("page", expr="repository.findOneCaseInsensitively(path)")
     * @IsGranted("ROLE_USER")
     * @IsGranted("write", subject="page", statusCode=403)
     *
     * @todo handle conflicts
     */
    public function edit(Request $request, WikiPage $page, EntityManagerInterface $em): Response {
        $data = WikiData::createFromPage($page);
        $form = $this->createForm(WikiType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updatePage($page, $this->getUser());

            $em->flush();

            return $this->redirectToRoute('wiki', [
                'path' => $page->getPath(),
            ]);
        }

        return $this->render('wiki/edit.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("lock", subject="page", statusCode=403)
     */
    public function lock(Request $request, WikiPage $page, bool $lock, EntityManagerInterface $em): Response {
        $this->validateCsrf('wiki_lock', $request->request->get('token'));

        $page->setLocked($lock);

        $em->flush();

        $this->addFlash('success', 'flash.page_'.($lock ? 'locked' : 'unlocked'));

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('wiki', ['path' => $page->getPath()]);
    }

    /**
     * @Entity("wikiPage", expr="repository.findOneCaseInsensitively(path)")
     */
    public function history(WikiPage $wikiPage, int $page): Response {
        return $this->render('wiki/history.html.twig', [
            'page' => $wikiPage,
            'revisions' => $wikiPage->getPaginatedRevisions($page),
        ]);
    }

    public function diff(Request $request, WikiRevisionRepository $repository): Response {
        /* @var WikiRevision $from
         * @var WikiRevision $to */
        [$from, $to] = array_map(function ($q) use ($request, $repository) {
            $id = $request->query->get($q);

            if (!Uuid::isValid($id)) {
                throw $this->createNotFoundException();
            }

            $revision = $repository->find($id);

            if (!$revision) {
                throw $this->createNotFoundException();
            }

            return $revision;
        }, ['from', 'to']);

        if ($from->getPage() !== $to->getPage()) {
            throw $this->createNotFoundException('Tried to compare two different pages');
        }

        return $this->render('wiki/diff.html.twig', [
            'from' => $from,
            'to' => $to,
            'page' => $from->getPage(),
        ]);
    }

    public function revision(WikiRevision $revision): Response {
        return $this->render('wiki/revision.html.twig', [
            'page' => $revision->getPage(),
            'revision' => $revision,
        ]);
    }

    public function all(int $page, WikiPageRepository $wikiPageRepository): Response {
        $pages = $wikiPageRepository->findAllPages($page);

        return $this->render('wiki/all.html.twig', [
            'pages' => $pages,
        ]);
    }

    public function recentChanges(WikiRevisionRepository $repository, int $page): Response {
        return $this->render('wiki/recent.html.twig', [
            'revisions' => $repository->findRecent($page),
        ]);
    }
}
