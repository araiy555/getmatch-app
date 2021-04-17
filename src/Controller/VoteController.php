<?php

namespace App\Controller;

use App\DataTransfer\VoteManager;
use App\Entity\Contracts\Votable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VoteController extends AbstractController {
    private $voteManager;

    public function __construct(VoteManager $voteManager) {
        $this->voteManager = $voteManager;
    }

    /**
     * Vote on a votable entity.
     *
     * @IsGranted("ROLE_USER")
     * @IsGranted("vote", subject="votable", statusCode=403)
     */
    public function __invoke(Request $request, Votable $votable): Response {
        $this->validateCsrf('vote', $request->request->get('token'));

        $user = $this->getUserOrThrow();
        $choice = $request->request->getInt('choice');
        $ip = $request->getClientIp();

        $this->voteManager->vote($votable, $user, $choice, $ip);

        if ($request->getRequestFormat() === 'json') {
            return $this->json(['netScore' => $votable->getNetScore()]);
        }

        if (!$request->headers->has('Referer')) {
            return $this->redirectToRoute('front');
        }

        return $this->redirect($request->headers->get('Referer'));
    }
}
