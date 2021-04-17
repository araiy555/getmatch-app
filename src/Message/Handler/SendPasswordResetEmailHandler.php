<?php

namespace App\Message\Handler;

use App\Entity\User;
use App\Message\SendPasswordResetEmail;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Security\PasswordResetHelper;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendPasswordResetEmailHandler implements MessageHandlerInterface {
    /**
     * @var PasswordResetHelper
     */
    private $helper;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @var string
     */
    private $noReplyAddress;

    public function __construct(
        PasswordResetHelper $helper,
        MailerInterface $mailer,
        SiteRepository $sites,
        UserRepository $users,
        TranslatorInterface $translator,
        string $noReplyAddress
    ) {
        $this->mailer = $mailer;
        $this->helper = $helper;
        $this->sites = $sites;
        $this->users = $users;
        $this->translator = $translator;
        $this->noReplyAddress = $noReplyAddress;
    }

    public function __invoke(SendPasswordResetEmail $message): void {
        $users = $this->users->lookUpByEmail($message->getEmailAddress());

        if (!$users) {
            throw new UnrecoverableMessageHandlingException('No users found');
        }

        $links = array_map(function (User $user) {
            return [
                'username' => $user->getUsername(),
                'url' => $this->helper->generateResetUrl($user),
            ];
        }, $users);

        $siteName = $this->sites->getCurrentSiteName();

        $mail = (new TemplatedEmail())
            ->to(new Address($message->getEmailAddress(), $users[0]->getUsername()))
            ->from(new Address($this->noReplyAddress, $siteName))
            ->subject($this->translator->trans('reset_password_email.subject', [
                '%site_name%' => $siteName,
            ]))
            ->textTemplate('reset_password/email.txt.twig')
            ->context(['links' => $links])
        ;

        // TODO: X-Originating-IP

        $this->mailer->send($mail);
    }
}
