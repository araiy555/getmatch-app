<?php

namespace App\Tests\EventListener;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Event\CommentCreated;
use App\Event\SubmissionCreated;
use App\EventListener\MentionsListener;
use App\Markdown\MarkdownConverter;
use App\Repository\UserRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;

/**
 * @covers \App\EventListener\MentionsListener
 */
class MentionsListenerTest extends TestCase {
    public function testSubmissionMentions(): void {
        $listener = $this->getListener($userParams);

        $submission = $this->createMock(Submission::class);
        $submission
            ->method('getBody')
            ->willReturn('some string');
        $submission
            ->expects($this->exactly(2))
            ->method('addMention')
            ->withConsecutive(...$userParams);

        $listener->onNewSubmission(new SubmissionCreated($submission));
    }

    public function testCommentMentions(): void {
        $listener = $this->getListener($userParams);

        $comment = $this->createMock(Comment::class);
        $comment
            ->method('addMention')
            ->withConsecutive(...$userParams);

        $listener->onNewComment(new CommentCreated($comment));
    }

    private function getListener(&$userParams): MentionsListener {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('flush');

        $converter = $this->createMock(MarkdownConverter::class);
        $converter
            ->expects($this->once())
            ->method('convertToHtml')
            ->willReturn(<<<EOF
<p>This is <a href="http://localhost/user/emma">/u/emma's</a> profile page. This
is someone else's <a href="http://localhost/user/someone/submissions">list of
submissions.</a></p>

<p>Here's <a href="http://localhost/user/zach">zach's page</a>. Here is an
<a href="http://example.com/user/unrelated">unrelated page on another host</a>.
Here is <a href="http://localhost/user/emma">emma's page</a> again.</p>
EOF
            );

        $users = [EntityFactory::makeUser(), EntityFactory::makeUser()];

        $userParams = array_map(function ($user) {
            return [$this->identicalTo($user)];
        }, $users);

        /** @var UserRepository|\PHPUnit\Framework\MockObject\MockObject $userRepository */
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findByNormalizedUsername'])
            ->getMock();
        $userRepository
            ->expects($this->once())
            ->method('findByNormalizedUsername')
            ->with(['emma', 'zach'])
            ->willReturn($users);

        $requestContext = new RequestContext('http://localhost');

        return new MentionsListener($manager, $converter, $requestContext, $userRepository);
    }
}
