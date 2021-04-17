<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\CommentData;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/comments", defaults={"_format": "json"}, requirements={"id": "%number_regex%"})
 */
final class CommentController extends AbstractController {
    /**
     * @Route("", methods={"GET"})
     */
    public function list(CommentRepository $comments): Response {
        return $this->json($comments->findPaginated(), 200, [], [
            'groups' => ['comment:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(Comment $comment): Response {
        return $this->json($comment, 200, [], [
            'groups' => ['comment:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("edit", subject="comment")
     */
    public function update(Comment $comment, EntityManagerInterface $em): Response {
        return $this->apiUpdate(new CommentData($comment), CommentData::class, [
            'normalization_groups' => ['comment:read'],
            'denormalization_groups' => ['comment:update'],
        ], function (CommentData $data) use ($comment, $em): void {
            $data->updateComment($comment, $this->getUser());

            $em->flush();
        });
    }
}
