<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\BanUserType;
use App\Form\IpBanType;
use App\Form\Model\IpBanData;
use App\Form\Model\UserBanData;
use App\Form\UnbanUserType;
use App\Repository\IpBanRepository;
use App\Repository\UserBanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_ADMIN", statusCode=403)
 * @Entity("user", expr="repository.findOneOrRedirectToCanonical(username, 'username')")
 */
final class BanController extends AbstractController {
    public function userBans(UserBanRepository $repository, int $page): Response {
        return $this->render('ban/user_bans.html.twig', [
            'bans' => $repository->findActiveBans($page),
        ]);
    }

    public function banUser(User $user, UserRepository $repository, EntityManagerInterface $em, Request $request): Response {
        $data = new UserBanData($repository->findIpsUsedByUser($user));

        $form = $this->createForm(BanUserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ban = $data->toUserBan($user, $this->getUser(), true);

            if ($form->get('ban_ip')->getData()) {
                foreach ($data->toIpBans($user, $this->getUser()) as $ipBan) {
                    $em->persist($ipBan);
                }
            }

            $em->persist($ban);
            $em->flush();

            return $this->redirectToRoute('user_bans');
        }

        return $this->render('ban/ban_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    public function unbanUser(User $user, EntityManagerInterface $em, Request $request): Response {
        $data = new UserBanData();

        $form = $this->createForm(UnbanUserType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $unban = $data->toUserBan($user, $this->getUser(), false);

            $em->persist($unban);

            if ($form->get('unban_ips')->getData()) {
                foreach ($user->getIpBans() as $ipBan) {
                    $em->remove($ipBan);
                }
            }

            $em->flush();

            $this->addFlash('success', 'flash.user_was_unbanned');

            return $this->redirectToRoute('user_bans');
        }

        return $this->render('ban/unban_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    public function ipBans(int $page, IpBanRepository $repository): Response {
        return $this->render('ban/ip_bans.html.twig', [
            'bans' => $repository->findAllPaginated($page),
        ]);
    }

    public function banIp(Request $request, EntityManagerInterface $em): Response {
        $data = new IpBanData();

        $form = $this->createForm(IpBanType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ban = $data->toIpBan($this->getUser());

            $em->persist($ban);
            $em->flush();

            $this->addFlash('success', 'flash.ban_added');

            return $this->redirectToRoute('ip_bans');
        }

        return $this->render('ban/ban_ip.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function unbanIps(Request $request, IpBanRepository $repository, EntityManagerInterface $em): Response {
        $this->validateCsrf('unban_ips', $request->request->get('token'));

        $ids = array_filter((array) $request->request->get('ban'), static function ($id) {
            return \is_int(+$id);
        });

        foreach ($repository->findBy(['id' => $ids]) as $ban) {
            $em->remove($ban);
        }

        $em->flush();

        return $this->redirectToRoute('ip_bans');
    }
}
