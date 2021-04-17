<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\ForumData;
use App\DataTransfer\ForumManager;
use App\Entity\Forum;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/forums", defaults={"_format": "json"}, requirements={"id": "%number_regex%"})
 */
final class ForumController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"})
     * @Route("/by_name/{name}", methods={"GET"})
     */
    public function read(Forum $forum): Response {
        return $this->json(ForumData::createFromForum($forum), 200, [], [
            'groups' => ['forum:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("", methods={"POST"})
     * @IsGranted("create_forum")
     */
    public function create(ForumManager $forumManager, EntityManagerInterface $em): Response {
        return $this->apiCreate(ForumData::class, [
            'normalization_groups' => ['forum:read', 'abbreviated_relations'],
            'denormalization_groups' => ['forum:create'],
            'validation_groups' => ['create_forum'],
        ], function (ForumData $data) use ($forumManager, $em) {
            $forum = $forumManager->createForum($data, $this->getUserOrThrow());

            $em->flush();

            return $forum;
        });
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("moderator", subject="forum")
     */
    public function update(Forum $forum, ForumManager $forumManager, EntityManagerInterface $em): Response {
        return $this->apiUpdate(ForumData::createFromForum($forum), ForumData::class, [
            'normalization_groups' => ['forum:read'],
            'denormalization_groups' => ['forum:update'],
            'validation_groups' => ['update_forum'],
        ], static function (ForumData $data) use ($forum, $forumManager, $em): void {
            $forumManager->updateForum($forum, $data);

            $em->flush();
        });
    }
}
