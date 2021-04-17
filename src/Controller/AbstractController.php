<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Security\Authentication;
use App\Utils\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractController extends BaseAbstractController {
    public static function getSubscribedServices(): array {
        return [
            Authentication::class,
            'slugger' => SluggerInterface::class,
            'validator' => ValidatorInterface::class,
        ] + parent::getSubscribedServices();
    }

    protected function getUser(): User {
        return $this->get(Authentication::class)->getUser();
    }

    protected function getUserOrThrow(): User {
        return $this->get(Authentication::class)->getUserOrThrow();
    }

    /**
     * @param string|mixed $token
     *
     * @throws BadRequestHttpException if the token isn't valid
     */
    protected function validateCsrf(string $id, $token): void {
        if (!\is_string($token) || !$this->isCsrfTokenValid($id, $token)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }
    }

    protected function createNamedForm(
        string $name,
        $type = FormType::class,
        $data = null,
        array $options = []
    ): FormInterface {
        return $this->container
            ->get('form.factory')
            ->createNamed($name, $type, $data, $options);
    }

    protected function generateSubmissionUrl(Submission $submission): string {
        $id = $submission->getId();

        if (!$id) {
            throw new \InvalidArgumentException('Cannot redirect to non-persisted submission');
        }

        return $this->generateUrl('submission', [
            'forum_name' => $submission->getForum()->getName(),
            'submission_id' => $id,
            'slug' => $this->get('slugger')->slugify($submission->getTitle()),
        ]);
    }

    protected function generateCommentUrl(Comment $comment): string {
        $id = $comment->getId();

        if (!$id) {
            throw new \InvalidArgumentException('Cannot redirect to non-persisted comment');
        }

        return $this->generateUrl('comment', [
            'forum_name' => $comment->getSubmission()->getForum()->getName(),
            'submission_id' => $comment->getSubmission()->getId(),
            'slug' => $this->get('slugger')->slugify($comment->getSubmission()->getTitle()),
            'comment_id' => $comment->getId(),
        ]);
    }

    protected function apiCreate(string $type, array $options, callable $handler): Response {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        \assert($request !== null);

        $serializer = $this->container->get('serializer');
        \assert($serializer instanceof SerializerInterface);

        $data = $serializer->deserialize($request->getContent(), $type, 'json', [
            'groups' => $options['denormalization_groups'] ?? [],
        ]);

        $validator = $this->container->get('validator');
        $errors = $validator->validate($data, null, $options['validation_groups'] ?? null);

        if (\count($errors) > 0) {
            return $this->json($errors, 400, ['Content-Type' => 'application/problem+json']);
        }

        return new Response($serializer->serialize($handler($data), 'json', [
            'groups' => $options['normalization_groups'] ?? [],
        ]), 201);
    }

    protected function apiUpdate($data, string $type, array $options, callable $handler): Response {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        \assert($request !== null);

        $serializer = $this->container->get('serializer');
        \assert($serializer instanceof SerializerInterface);

        $serializer->deserialize($request->getContent(), $type, 'json', [
            'groups' => $options['denormalization_groups'] ?? [],
            'object_to_populate' => $data,
        ]);

        $validator = $this->container->get('validator');
        $errors = $validator->validate($data, null, $options['validation_groups'] ?? null);

        if (\count($errors) > 0) {
            return $this->json($errors, 400, ['Content-Type' => 'application/problem+json']);
        }

        $handler($data);

        return $this->createEmptyResponse();
    }

    protected function createEmptyResponse(): Response {
        $response = new Response('', 204);
        $response->headers->remove('Content-Type');

        return $response;
    }
}
