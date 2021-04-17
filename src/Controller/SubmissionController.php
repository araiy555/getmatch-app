<?php

namespace App\Controller;

use App\DataObject\SubmissionData;
use App\DataTransfer\SubmissionManager;
use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Form\DeleteReasonType;
use App\Form\SubmissionType;
use App\Repository\CommentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Entity("forum", expr="repository.findOneOrRedirectToCanonical(forum_name, 'forum_name')")
 * @Entity("submission", expr="repository.findOneBy({forum: forum, id: submission_id})")
 * @Entity("comment", expr="repository.findOneBy({submission: submission, id: comment_id})")
 */
final class SubmissionController extends AbstractController {
    /**
     * @var CommentRepository
     */
    private $comments;

    /**
     * @var SubmissionManager
     */
    private $manager;

    public function __construct(CommentRepository $comments, SubmissionManager $manager) {
        $this->comments = $comments;
        $this->manager = $manager;
    }

    /**
     * Show a submission's comment page.
     *
     * @IsGranted("view", subject="submission", statusCode=403)
     *
     * @Cache(smaxage="10 seconds")
     */
    public function submission(Forum $forum, Submission $submission, string $commentView): Response {
        if ($commentView === 'nested') {
            $comments = $submission->getTopLevelComments();
        } else {
            $comments = $submission->getComments();
        }

        $this->comments->hydrate(...$comments);

        return $this->render('submission/submission.html.twig', [
            'comments' => $comments,
            'comment_view' => $commentView,
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @IsGranted("view", subject="submission", statusCode=403)
     */
    public function submissionJson(Forum $forum, Submission $submission): Response {
        return $this->json($submission, 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * Show a single comment and its replies.
     *
     * @IsGranted("view", subject="submission", statusCode=403)
     * @IsGranted("view", subject="comment", statusCode=403)
     */
    public function commentPermalink(Forum $forum, Submission $submission, Comment $comment): Response {
        $this->comments->hydrate($comment, ...$comment->getChildrenRecursive());

        return $this->render('submission/comment.html.twig', [
            'comment' => $comment,
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @Entity("submission", expr="repository.find(id)")
     * @IsGranted("view", subject="submission", statusCode=403)
     */
    public function shortcut(Submission $submission): Response {
        return $this->redirect($this->generateSubmissionUrl($submission));
    }

    /**
     * Create a new submission.
     *
     * @IsGranted("ROLE_USER")
     */
    public function submit(?Forum $forum, Request $request): Response {
        $data = new SubmissionData();
        $data->setForum($forum);

        $form = $this->createForm(SubmissionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $user = $this->getUserOrThrow();
            $ip = $request->getClientIp();

            $submission = $this->manager->submit($data, $user, $ip);

            return $this->redirect($this->generateSubmissionUrl($submission));
        }

        return $this->render('submission/create.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="submission", statusCode=403)
     */
    public function editSubmission(Forum $forum, Submission $submission, Request $request): Response {
        $data = SubmissionData::createFromSubmission($submission);

        $form = $this->createForm(SubmissionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->update($submission, $data, $this->getUser());

            $this->addFlash('success', 'flash.submission_edited');

            return $this->redirect($this->generateSubmissionUrl($submission));
        }

        return $this->render('submission/edit.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Security("is_granted('mod_delete', submission)", statusCode=403)
     */
    public function modDelete(Request $request, Forum $forum, Submission $submission): Response {
        $form = $this->createForm(DeleteReasonType::class, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUserOrThrow();
            $reason = $form->getData()['reason'];

            $this->manager->remove($submission, $user, $reason);

            $this->addFlash('success', 'flash.submission_deleted');

            return $this->redirectToRoute('forum', [
                'forum_name' => $forum->getName(),
            ]);
        }

        return $this->render('submission/delete_with_reason.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'submission' => $submission,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("delete_own", subject="submission", statusCode=403)
     */
    public function deleteOwn(Request $request, Forum $forum, Submission $submission): Response {
        $this->validateCsrf('delete_submission', $request->request->get('token'));

        $this->manager->delete($submission);

        $this->addFlash('success', 'flash.submission_deleted');

        return $this->redirectAfterDelete($request);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("purge", subject="submission", statusCode=403)
     */
    public function purge(Forum $forum, Submission $submission, Request $request): Response {
        $this->validateCsrf('purge_submission', $request->request->get('token'));

        $this->manager->purge($submission);

        $this->addFlash('success', 'flash.submission_purged');

        return $this->redirectAfterDelete($request);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("restore", subject="submission", statusCode=403)
     */
    public function restore(Forum $forum, Submission $submission, Request $request): Response {
        $this->validateCsrf('restore_submission', $request->request->get('token'));

        $this->manager->restore($submission, $this->getUserOrThrow());

        $this->addFlash('success', 'flash.submission_restored');

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirect($this->generateSubmissionUrl($submission));
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("lock", subject="submission", statusCode=403)
     */
    public function lock(Request $request, Forum $forum, Submission $submission, bool $lock): Response {
        $this->validateCsrf('lock', $request->request->get('token'));

        $data = SubmissionData::createFromSubmission($submission);
        $data->setLocked($lock);

        $this->manager->update($submission, $data, $this->getUserOrThrow());

        if ($lock) {
            $this->addFlash('success', 'flash.submission_locked');
        } else {
            $this->addFlash('success', 'flash.submission_unlocked');
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirect($this->generateSubmissionUrl($submission));
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("pin", subject="submission", statusCode=403)
     */
    public function pin(Request $request, Forum $forum, Submission $submission, bool $pin): Response {
        $this->validateCsrf('pin', $request->request->get('token'));

        $data = SubmissionData::createFromSubmission($submission);
        $data->setSticky($pin);

        $this->manager->update($submission, $data, $this->getUserOrThrow());

        if ($pin) {
            $this->addFlash('success', 'flash.submission_pinned');
        } else {
            $this->addFlash('success', 'flash.submission_unpinned');
        }

        if ($request->headers->has('Referer')) {
            return $this->redirect($request->headers->get('Referer'));
        }

        return $this->redirect($this->generateSubmissionUrl($submission));
    }

    private function redirectAfterDelete(Request $request): Response {
        $url = $request->headers->get('Referer', '');
        preg_match('!/f/[^/]++/(\d+)!', $url, $matches);

        if (!$url || $request->attributes->get('submission_id') === ($matches[1] ?? '')) {
            $url = $this->generateUrl('forum', [
                'forum_name' => $request->attributes->get('forum_name'),
            ]);
        }

        return $this->redirect($url);
    }
}
