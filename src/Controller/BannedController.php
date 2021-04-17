<?php

namespace App\Controller;

use App\Repository\IpBanRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BannedController extends AbstractController {
    public function __invoke(Request $request, IpBanRepository $ipBans): Response {
        $user = $this->getUser();

        $userBan = $user ? $user->getActiveBan() : null;
        $ipBans = $ipBans->findActiveBans($request->getClientIp());

        if (!$userBan && \count($ipBans) === 0) {
            return $this->redirectToRoute('front');
        }

        return new Response($this->renderView('ban/banned.html.twig', [
            'ip_bans' => $ipBans,
            'user_ban' => $userBan,
        ]), 403);
    }
}
