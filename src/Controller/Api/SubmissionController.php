<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\DataObject\SubmissionData;
use App\DataTransfer\SubmissionManager;
use App\Entity\Submission;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\SubmissionFinder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/submissions", defaults={"_format": "json"}, requirements={"id": "%number_regex%"})
 */
final class SubmissionController extends AbstractController {
    /**
     * @Route("", methods={"GET"})
     */
    public function list(Request $request, SubmissionFinder $finder): Response {
        $user = $this->getUserOrThrow();
        $sortBy = $request->query->get('sortBy', $user->getFrontPageSortMode());

        if (!\in_array($sortBy, Submission::SORT_OPTIONS, true)) {
            return $this->json(['message' => 'unknown sort mode'], 400);
        }

        $criteria = new Criteria($sortBy);

        switch ($request->query->get('filter', $user->getFrontPage())) {
        case Submission::FRONT_FEATURED:
            $criteria
                ->showFeatured()
                ->excludeHiddenForums();
            break;
        case Submission::FRONT_SUBSCRIBED:
            $criteria
                ->showSubscribed()
                ->excludeBlockedUsers();
            break;
        case Submission::FRONT_MODERATED:
            $criteria->showModerated();
            break;
        case Submission::FRONT_ALL:
            $criteria
                ->excludeHiddenForums()
                ->excludeBlockedUsers();
            break;
        default:
            return $this->json(['message' => 'unknown filter mode', 400]);
        }

        return $this->json($finder->find($criteria), 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function read(Submission $submission): Response {
        return $this->json($submission, 200, [], [
            'groups' => ['submission:read', 'abbreviated_relations'],
        ]);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function create(SubmissionManager $manager, Request $request): Response {
        return $this->apiCreate(SubmissionData::class, [
            'normalization_groups' => ['submission:read', 'abbreviated_relations'],
            'denormalization_groups' => ['submission:create'],
            'validation_groups' => ['create'],
        ], function (SubmissionData $data) use ($manager, $request) {
            $user = $this->getUserOrThrow();
            $ip = $request->getClientIp();

            return $manager->submit($data, $user, $ip);
        });
    }

    /**a
     * @Route("/{id}", methods={"PUT"})
     * @IsGranted("edit", subject="submission")
     */
    public function update(Submission $submission, SubmissionManager $manager): Response {
        $data = SubmissionData::createFromSubmission($submission);

        return $this->apiUpdate($data, SubmissionData::class, [
            'normalization_groups' => ['submission:read'],
            'denormalization_groups' => ['submission:update'],
            'validation_groups' => ['update'],
        ], function (SubmissionData $data) use ($manager, $submission): void {
            $manager->update($submission, $data, $this->getUserOrThrow());
        });
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     * @IsGranted("delete_own", subject="submission")
     */
    public function delete(Submission $submission, SubmissionManager $manager): Response {
        $manager->delete($submission);

        return $this->createEmptyResponse();
    }

    /**
     * @Route("/{id}/comments", methods={"GET"})
     */
    public function comments(Submission $submission): Response {
        return $this->json($submission->getTopLevelComments(), 200, [], [
            'groups' => ['comment:read', 'comment:nested', 'abbreviated_relations'],
        ]);
    }
}
