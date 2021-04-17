<?php

namespace App\Controller;

use App\DataObject\ForumData;
use App\DataTransfer\ForumManager;
use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\User;
use App\Form\ConfirmDeletionType;
use App\Form\ForumAppearanceType;
use App\Form\ForumBanType;
use App\Form\ForumType;
use App\Form\Model\ForumBanData;
use App\Form\Model\ModeratorData;
use App\Form\ModeratorType;
use App\Repository\CommentRepository;
use App\Repository\ForumBanRepository;
use App\Repository\ForumLogEntryRepository;
use App\Repository\ForumRepository;
use App\Repository\TrashRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Entity("forum", expr="repository.findOneOrRedirectToCanonical(forum_name, 'forum_name')")
 * @Entity("user", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
 */
final class ForumController extends AbstractController {
    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    public function __construct(SubmissionFinder $submissionFinder) {
        $this->submissionFinder = $submissionFinder;
    }

    /**
     * Show the front page of a given forum.
     */
    public function front(Forum $forum, ?string $sortBy, string $_format): Response {
        $criteria = (new Criteria($sortBy))
            ->showForums($forum)
            ->stickiesFirst()
            ->excludeBlockedUsers();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render("forum/forum.$_format.twig", [
            'forum' => $forum,
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    public function multi(ForumRepository $fr, string $names, ?string $sortBy): Response {
        $names = array_map(Forum::class.'::normalizeName', explode('+', $names));
        $forums = $fr->findByNormalizedName($names);

        if (!$forums) {
            throw $this->createNotFoundException('no such forums');
        }

        $criteria = (new Criteria($sortBy))
            ->showForums(...$forums)
            ->excludeBlockedUsers();

        $submissions = $this->submissionFinder->find($criteria);

        return $this->render('forum/multi.html.twig', [
            'forums' => $names,
            'sort_by' => $this->submissionFinder->getSortMode($sortBy),
            'submissions' => $submissions,
        ]);
    }

    /**
     * Create a new forum.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("create_forum", statusCode=403)
     */
    public function createForum(
        Request $request,
        ForumManager $forumManager,
        EntityManagerInterface $em
    ): Response {
        $data = new ForumData();

        $form = $this->createForm(ForumType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum = $forumManager->createForum($data, $this->getUserOrThrow());

            $em->flush();

            return $this->redirectToRoute('forum', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum", statusCode=403)
     */
    public function editForum(
        Forum $forum,
        Request $request,
        ForumManager $forumManager,
        EntityManagerInterface $em
    ): Response {
        $data = ForumData::createFromForum($forum);

        $form = $this->createForm(ForumType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forumManager->updateForum($forum, $data);

            $em->flush();
            $this->addFlash('success', 'flash.forum_updated');

            return $this->redirectToRoute('edit_forum', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/edit.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("delete", subject="forum", statusCode=403)
     */
    public function delete(Request $request, Forum $forum, EntityManagerInterface $em): Response {
        $form = $this->createForm(ConfirmDeletionType::class, null, [
            'name' => $forum->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($forum);
            $em->flush();

            $this->addFlash('notice', 'flash.forum_deleted');

            return $this->redirectToRoute('front');
        }

        return $this->render('forum/delete.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    public function comments(CommentRepository $comments, Forum $forum): Response {
        return $this->render('forum/comments.html.twig', [
            'comments' => $comments->findPaginatedByForum($forum),
            'forum' => $forum,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    public function subscribe(Request $request, EntityManagerInterface $em, Forum $forum, bool $subscribe, string $_format): Response {
        $this->validateCsrf('subscribe', $request->request->get('token'));

        if ($subscribe) {
            $forum->subscribe($this->getUser());
        } else {
            $forum->unsubscribe($this->getUser());
        }

        $em->flush();

        if ($_format === 'json') {
            return $this->json(['subscribed' => $subscribe]);
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirectToRoute('forum', ['forum_name' => $forum->getName()]);
    }

    public function list(ForumRepository $repository, int $page, string $sortBy): Response {
        return $this->render('forum/list.html.twig', [
            'forums' => $repository->findForumsByPage($page, $sortBy),
            'sortBy' => $sortBy,
        ]);
    }

    public function listAll(ForumRepository $forums): Response {
        return $this->render('forum/list_all.html.twig', [
            'forums' => $forums->findAllForumNames(),
        ]);
    }

    /**
     * Show a list of forum moderators.
     */
    public function moderators(Forum $forum, int $page): Response {
        return $this->render('forum/moderators.html.twig', [
            'forum' => $forum,
            'moderators' => $forum->getPaginatedModerators($page),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum")
     */
    public function trash(Forum $forum, TrashRepository $posts): Response {
        return $this->render('forum/trash.html.twig', [
            'forum' => $forum,
            'trash' => $posts->findTrashInForum($forum),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function addModerator(EntityManagerInterface $em, Forum $forum, Request $request): Response {
        $data = new ModeratorData($forum);
        $form = $this->createForm(ModeratorType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($data->toModerator());
            $em->flush();

            $this->addFlash('success', 'flash.forum_moderator_added');

            return $this->redirectToRoute('forum_moderators', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/add_moderator.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    /**
     * @Entity("moderator", expr="repository.findOneBy({'forum': forum, 'id': moderator_id})")
     * @IsGranted("ROLE_USER")
     * @IsGranted("remove", subject="moderator", statusCode=403)
     */
    public function removeModerator(EntityManagerInterface $em, Forum $forum, Request $request, Moderator $moderator): Response {
        $this->validateCsrf('remove_moderator', $request->request->get('token'));

        $em->remove($moderator);
        $em->flush();

        $this->addFlash('success', 'flash.user_unmodded');

        return $this->redirectToRoute('forum_moderators', [
            'forum_name' => $forum->getName(),
        ]);
    }

    public function moderationLog(Forum $forum, int $page): Response {
        return $this->render('forum/moderation_log.html.twig', [
            'forum' => $forum,
            'logs' => $forum->getPaginatedLogEntries($page),
        ]);
    }

    public function globalModerationLog(ForumLogEntryRepository $forumLogs, int $page): Response {
        return $this->render('forum/global_moderation_log.html.twig', [
            'logs' => $forumLogs->findAllPaginated($page),
        ]);
    }

    /**
     * Alter a forum's appearance.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum", statusCode=403)
     */
    public function appearance(
        Forum $forum,
        Request $request,
        ForumManager $forumManager,
        EntityManagerInterface $em
    ): Response {
        $data = ForumData::createFromForum($forum);

        $form = $this->createForm(ForumAppearanceType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forumManager->updateForum($forum, $data);

            $em->flush();

            return $this->redirectToRoute('forum_appearance', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/appearance.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    public function bans(Forum $forum, ForumBanRepository $banRepository, int $page = 1): Response {
        return $this->render('forum/bans.html.twig', [
            'bans' => $banRepository->findValidBansInForum($forum, $page),
            'forum' => $forum,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum", statusCode=403)
     */
    public function banHistory(Forum $forum, User $user, int $page = 1): Response {
        return $this->render('forum/ban_history.html.twig', [
            'bans' => $forum->getPaginatedBansByUser($user, $page),
            'forum' => $forum,
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum", statusCode=403)
     */
    public function ban(Forum $forum, User $user, Request $request, EntityManagerInterface $em): Response {
        $data = new ForumBanData();

        $form = $this->createForm(ForumBanType::class, $data, ['intent' => 'ban']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->addBan($data->toBan($forum, $user, $this->getUser()));

            $em->flush();

            $this->addFlash('success', 'flash.user_was_banned');

            return $this->redirectToRoute('forum_bans', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/ban.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'user' => $user,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("moderator", subject="forum", statusCode=403)
     */
    public function unban(Forum $forum, User $user, Request $request, EntityManagerInterface $em): Response {
        $data = new ForumBanData();

        $form = $this->createForm(ForumBanType::class, $data, ['intent' => 'unban']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forum->addBan($data->toUnban($forum, $user, $this->getUser()));

            $em->flush();

            $this->addFlash('success', 'flash.user_was_unbanned');

            return $this->redirectToRoute('forum_bans', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('forum/unban.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'user' => $user,
        ]);
    }
}
