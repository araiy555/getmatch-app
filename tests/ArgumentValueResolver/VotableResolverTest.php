<?php

namespace App\Tests\ArgumentValueResolver;

use App\ArgumentValueResolver\VotableResolver;
use App\Entity\Comment;
use App\Entity\Contracts\Votable;
use App\Entity\Submission;
use App\Repository\CommentRepository;
use App\Repository\SubmissionRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @covers \App\ArgumentValueResolver\VotableResolver
 */
class VotableResolverTest extends TestCase {
    /**
     * @var SubmissionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $submissions;

    /**
     * @var CommentRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $comments;

    private $resolver;

    protected function setUp(): void {
        $this->submissions = $this->createMock(SubmissionRepository::class);
        $this->comments = $this->createMock(CommentRepository::class);

        $this->resolver = new VotableResolver($this->submissions, $this->comments);
    }

    /**
     * @dataProvider provideSupportsParams
     */
    public function testSupports(bool $supports, Request $request, ArgumentMetadata $argument): void {
        $this->assertSame($supports, $this->resolver->supports($request, $argument));
    }

    public function provideSupportsParams(): \Generator {
        $request = new Request();
        $request->attributes->replace(['entityClass' => Submission::class, 'id' => 420]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);
        yield 'supports submission' => [true, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['entityClass' => Comment::class, 'id' => 69]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);
        yield 'supports comment' => [true, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['entityClass' => \Exception::class, 'id' => 1312]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);
        yield 'wrong entityClass' => [false, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['id' => 420]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);
        yield 'missing entityClass' => [false, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['entityClass' => Submission::class]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);
        yield 'missing id' => [false, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['entityClass' => Submission::class, 'id' => 420]);
        $argument = new ArgumentMetadata('something', \Exception::class, false, false, null, false);
        yield 'wrong type' => [false, $request, $argument];

        $request = new Request();
        $request->attributes->replace(['entityClass' => Submission::class, 'id' => 420]);
        $argument = new ArgumentMetadata('something', Votable::class, true, false, null, false);
        yield 'unsupported variadic' => [false, $request, $argument];
    }

    public function testResolveSubmission(): void {
        $request = new Request();
        $request->attributes->replace(['entityClass' => Submission::class, 'id' => 420]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);

        $submission = EntityFactory::makeSubmission();

        $this->submissions
            ->expects($this->once())
            ->method('find')
            ->with(420)
            ->willReturn($submission);

        $this->assertContains($submission, $this->resolver->resolve($request, $argument));
    }

    public function testResolveComment(): void {
        $request = new Request();
        $request->attributes->replace(['entityClass' => Comment::class, 'id' => 69]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);

        $comment = EntityFactory::makeComment();

        $this->comments
            ->expects($this->once())
            ->method('find')
            ->with(69)
            ->willReturn($comment);

        $this->assertContains($comment, $this->resolver->resolve($request, $argument));
    }

    public function testResolveInvalidEntity(): void {
        $request = new Request();
        $request->attributes->replace(['entityClass' => \Exception::class, 'id' => 1312]);
        $argument = new ArgumentMetadata('something', Votable::class, false, false, null, false);

        $this->expectException(\LogicException::class);

        $this->resolver->resolve($request, $argument)->next();
    }
}
