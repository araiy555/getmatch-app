<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\CommentCreated;
use App\Event\SubmissionCreated;
use App\Markdown\MarkdownConverter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RequestContext;

final class MentionsListener implements EventSubscriberInterface {
    private const USER_URL_PATTERN = '!^%s/user/(\w{3,25})$!';

    /**
     * @var MarkdownConverter
     */
    private $converter;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var UserRepository
     */
    private $users;

    public static function getSubscribedEvents(): array {
        return [
            CommentCreated::class => ['onNewComment'],
            SubmissionCreated::class => ['onNewSubmission'],
        ];
    }

    public function __construct(
        EntityManagerInterface $manager,
        MarkdownConverter $converter,
        RequestContext $requestContext,
        UserRepository $users
    ) {
        $this->converter = $converter;
        $this->manager = $manager;
        $this->requestContext = $requestContext;
        $this->users = $users;
    }

    public function onNewSubmission(SubmissionCreated $event): void {
        $submission = $event->getSubmission();

        if ($submission->getBody() === null) {
            return;
        }

        $html = $this->converter->convertToHtml($submission->getBody());
        $users = $this->getUsersToNotify($html);

        foreach ($users as $user) {
            $submission->addMention($user);
        }

        $this->manager->flush();
    }

    public function onNewComment(CommentCreated $event): void {
        $comment = $event->getComment();
        $html = $this->converter->convertToHtml($comment->getBody());
        $users = $this->getUsersToNotify($html);

        foreach ($users as $user) {
            $comment->addMention($user);
        }

        $this->manager->flush();
    }

    /**
     * @return User[]
     */
    private function getUsersToNotify(string $html): array {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML($html);

        $links = $document->documentElement->getElementsByTagName('a');
        $pattern = sprintf(
            self::USER_URL_PATTERN,
            preg_quote($this->requestContext->getBaseUrl(), '!')
        );
        $count = 0;

        foreach ($links as $node) {
            \assert($node instanceof \DOMElement);
            $href = $node->getAttribute('href');

            if (preg_match($pattern, $href, $matches)) {
                $usernames[] = User::normalizeUsername($matches[1]);
            }

            if (++$count === 25) {
                break;
            }
        }

        $usernames = array_unique($usernames ?? []);

        return $this->users->findByNormalizedUsername($usernames);
    }
}
