<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\TrashRepository;
use App\Repository\ForumRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actions that list submissions across many forums.
 *
 * @Cache(smaxage="10 seconds")
 */
final class FrontController extends AbstractController {
    /**
     * @var ForumRepository
     */
    private $forums;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    public function __construct(
        ForumRepository $forums,
        SubmissionFinder $submissionFinder
    ) {
        $this->forums = $forums;
        $this->submissionFinder = $submissionFinder;
    }

    public function front(?string $sortBy): Response {
        if ($this->isGranted('ROLE_USER')) {
            $user = $this->getUser();
            \assert($user instanceof \App\Entity\User);

            $listing = $user->getFrontPage();

            if (
                $listing === Submission::FRONT_SUBSCRIBED &&
                $user->getSubscriptionCount() === 0
            ) {
                $listing = Submission::FRONT_FEATURED;
            }
        } else {
            $listing = Submission::FRONT_FEATURED;
        }

        return $this->$listing($sortBy, 'html');
    }

    public function featured(?string $sortBy, string $_format): Response {
        $criteria = (new Criteria($sortBy))
            ->showFeatured()
            ->excludeBlockedUsers()
            ->excludeHiddenForums();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("front/featured.$_format.twig", [
            'forums' => $this->forums->findFeaturedForumNames(),
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function subscribed(?string $sortBy): Response {
        $forums = $this->forums->findSubscribedForumNames($this->getUser());

        if (!$forums) {
            // To avoid showing new users a blank page, we show them the
            // featured forums instead.
            return $this->redirectToRoute('featured', ['sortBy' => $sortBy]);
        }

        $criteria = (new Criteria($sortBy))
            ->excludeBlockedUsers()
            ->showSubscribed();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render('front/subscribed.html.twig', [
            'forums' => $forums,
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    public function all(?string $sortBy, string $_format): Response {
        $criteria = (new Criteria($sortBy))
            ->excludeBlockedUsers()
            ->excludeHiddenForums();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("front/all.$_format.twig", [
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function moderated(?string $sortBy): Response {
        $forums = $this->forums->findModeratedForumNames($this->getUser());

        $criteria = (new Criteria($sortBy))
            ->showModerated();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render('front/moderated.html.twig', [
            'forums' => $forums,
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function trash(TrashRepository $trash): Response {
        $forums = $this->forums->findModeratedForumNames($this->getUser());

        return $this->render('front/trash.html.twig', [
            'forums' => $forums,
            'trash' => $trash->findTrashForUser($this->getUser()),
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function globalTrash(TrashRepository $trash): Response {
        return $this->render('front/trash.html.twig', [
            'trash' => $trash->findTrash(),
        ]);
    }
}
