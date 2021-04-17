<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Form\MessageType;
use App\Form\Model\MessageData;
use App\Repository\MessageThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_USER")
 */
final class MessageController extends AbstractController {
    public function threads(MessageThreadRepository $repository, int $page): Response {
        $messageThreads = $repository->findUserMessages($this->getUser(), $page);

        return $this->render('message/threads.html.twig', [
            'threads' => $messageThreads,
        ]);
    }

    /**
     * Start a new message thread.
     *
     * @IsGranted("message", subject="receiver", statusCode=403)
     * @Entity("receiver", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
     */
    public function compose(Request $request, EntityManagerInterface $em, User $receiver): Response {
        $data = new MessageData();

        $form = $this->createForm(MessageType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread = $data->toThread($this->getUser(), $receiver, $request->getClientIp());

            $em->persist($thread);
            $em->flush();

            return $this->redirectToRoute('message_thread', [
                'id' => $thread->getId(),
            ]);
        }

        return $this->render('message/compose.html.twig', [
            'form' => $form->createView(),
            'user' => $receiver,
        ]);
    }

    /**
     * View a message thread.
     *
     * @IsGranted("access", subject="thread", statusCode=403)
     */
    public function thread(MessageThread $thread): Response {
        return $this->render('message/thread.html.twig', [
            'thread' => $thread,
        ]);
    }

    public function replyForm(string $threadId): Response {
        $form = $this->createForm(MessageType::class, null, [
            'action' => $this->generateUrl('reply_to_message', [
                'id' => $threadId,
            ]),
        ]);

        return $this->render('message/reply_form_fragment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("reply", subject="thread", statusCode=403)
     */
    public function reply(Request $request, EntityManagerInterface $em, MessageThread $thread): Response {
        $data = new MessageData();

        $form = $this->createForm(MessageType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $data->toMessage($thread, $this->getUser(), $request->getClientIp());
            $thread->addMessage($message);

            $em->flush();

            return $this->redirectToRoute('message_thread', [
                'id' => $thread->getId(),
            ]);
        }

        return $this->render('message/reply_errors.html.twig', [
            'form' => $form->createView(),
            'thread' => $thread,
        ]);
    }

    /**
     * @IsGranted("delete", subject="message", statusCode=403)
     */
    public function delete(Request $request, EntityManagerInterface $em, Message $message): Response {
        $this->validateCsrf('delete_message', $request->request->get('token'));

        $em->refresh($message);

        $thread = $message->getThread();
        $thread->removeMessage($message);

        if (\count($thread->getMessages()) === 0) {
            $em->remove($thread);
            $threadRemove = true;
        }

        $em->flush();

        if ($threadRemove ?? false) {
            return $this->redirectToRoute('message_threads');
        }

        return $this->redirectToRoute('message_thread', [
            'id' => $thread->getId(),
        ]);
    }
}
