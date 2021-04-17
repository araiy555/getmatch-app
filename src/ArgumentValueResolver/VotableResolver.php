<?php

namespace App\ArgumentValueResolver;

use App\Entity\Comment;
use App\Entity\Contracts\Votable;
use App\Entity\Submission;
use App\Repository\CommentRepository;
use App\Repository\SubmissionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class VotableResolver implements ArgumentValueResolverInterface {
    /**
     * @var SubmissionRepository
     */
    private $submissions;

    /**
     * @var CommentRepository
     */
    private $comments;

    public function __construct(
        SubmissionRepository $submissions,
        CommentRepository $comments
    ) {
        $this->submissions = $submissions;
        $this->comments = $comments;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool {
        return $argument->getType() === Votable::class &&
            !$argument->isVariadic() &&
            $request->attributes->has('entityClass') &&
            \in_array($request->attributes->get('entityClass'), [
                Submission::class,
                Comment::class,
            ], true) &&
            $request->attributes->has('id');
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator {
        ['id' => $id, 'entityClass' => $entityClass] = $request->attributes->all();

        switch ($entityClass) {
        case Submission::class:
            return yield $this->submissions->find($id);
        case Comment::class:
            return yield $this->comments->find($id);
        }

        throw new \LogicException('Unknown entity class');
    }
}
