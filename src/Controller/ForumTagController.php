<?php

namespace App\Controller;

use App\Entity\ForumTag;
use App\Form\ForumTagType;
use App\DataObject\ForumTagData;
use App\Repository\ForumTagRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Entity("tag", expr="repository.findByNameOrRedirectToCanonical(name, 'name')")
 */
final class ForumTagController extends AbstractController {
    public function list(ForumTagRepository $tags, int $page): Response {
        return $this->render('forum_tag/list.html.twig', [
            'tags' => $tags->findPaginated($page),
        ]);
    }

    public function tag(ForumTag $tag, ?string $sortBy, SubmissionFinder $submissionFinder): Response {
        $criteria = (new Criteria($sortBy))
            ->showForums(...$tag->getForums())
            ->excludeHiddenForums()
            ->excludeBlockedUsers();

        $submissions = $submissionFinder->find($criteria);

        return $this->render('forum_tag/tag.html.twig', [
            'sort_by' => $submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
            'tag' => $tag,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function edit(ForumTag $tag, Request $request, EntityManagerInterface $em): Response {
        $data = ForumTagData::createFromForumTag($tag);

        $form = $this->createForm(ForumTagType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateForumTag($tag);

            $em->flush();

            return $this->redirectToRoute('forum_tag', ['name' => $tag->getName()]);
        }

        return $this->render('forum_tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form->createView(),
        ]);
    }
}
