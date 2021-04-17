<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\ModeratorData;
use App\DataObject\UserData;
use App\Entity\Submission;
use App\Entity\User;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/users", defaults={"_format": "json"}, requirements={"id": "%number_regex%"})
 */
final class UserController extends AbstractController {
    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(User $user): Response {
        return $this->json($user, 200, [], [
            'groups' => ['user:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/self", methods={"GET"})
     */
    public function self(): Response {
        return $this->json($this->getUser(), 200, [], [
            'groups' => ['abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}/preferences", methods={"GET"})
     * @IsGranted("edit_user", subject="user")
     */
    public function readPreferences(User $user): Response {
        return $this->json($user, 200, [], [
            'groups' => ['user:preferences'],
        ]);
    }

    /**
     * @Route("/{id}/preferences", methods={"PUT"})
     * @IsGranted("edit_user", subject="user")
     */
    public function updatePreferences(User $user, EntityManagerInterface $em): Response {
        return $this->apiUpdate(new UserData($user), UserData::class, [
            'normalization_groups' => ['user:preferences'],
            'denormalization_groups' => ['user:preferences'],
            'validation_groups' => ['settings'],
        ], static function (UserData $data) use ($em, $user): void {
            $data->updateUser($user);

            $em->flush();
        });
    }

    /**
     * @Route("/{id}/submissions", methods={"GET"})
     */
    public function readSubmissions(User $user, SubmissionFinder $finder): Response {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers($user);

        return $this->json($finder->find($criteria), 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}/moderator_of", methods={"GET"})
     */
    public function readModeratedForums(User $user): Response {
        // TODO: pagination
        return $this->json([
            'entries' => $user->getModeratorTokens()->map(static function ($token) {
                return new ModeratorData($token);
            }),
        ], 200, [], [
            'groups' => ['moderator:user-side', 'abbreviated_relations'],
        ]);
    }
}
