<?php

namespace App\Controller;

use App\DataObject\UserData;
use App\Entity\User;
use App\Form\RequestPasswordResetType;
use App\Form\UserType;
use App\Message\SendPasswordResetEmail;
use App\Message\Stamp\RequestInfoStamp;
use App\Security\PasswordResetHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResetPasswordController extends AbstractController {
    /**
     * @var PasswordResetHelper
     */
    private $helper;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(EntityManagerInterface $manager, PasswordResetHelper $helper) {
        $this->helper = $helper;
        $this->manager = $manager;
    }

    public function requestReset(Request $request): Response {
        if (!$this->helper->canReset()) {
            $view = $this->renderView('reset_password/cannot_reset.html.twig');

            return new Response($view, 404);
        }

        $form = $this->createForm(RequestPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()->getEmail();

            $this->dispatchMessage(new SendPasswordResetEmail($email), [
                RequestInfoStamp::createFromRequest($request),
            ]);

            $this->addFlash('success', 'flash.reset_password_email_sent');

            return $this->redirectToRoute('front');
        }

        return $this->render('reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function reset(string $checksum, User $user, int $expires, Request $request): Response {
        $this->helper->denyUnlessValidChecksum($checksum, $user, $expires);

        $data = new UserData($user);

        $form = $this->createForm(UserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateUser($user);

            $this->manager->flush();

            $this->addFlash('success', 'flash.user_password_updated');

            return $this->redirectToRoute('front');
        }

        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
