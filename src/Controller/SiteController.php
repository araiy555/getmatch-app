<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\Model\SiteData;
use App\Form\SiteSettingsType;
use App\Repository\TrashRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SiteController extends AbstractController {
    public function healthCheck(): Response {
        return new Response('It works!', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     *
     * @Entity("site", expr="repository.findCurrentSite()")
     */
    public function settings(Site $site, Request $request, EntityManagerInterface $em): Response {
        $data = SiteData::createFromSite($site);

        $form = $this->createForm(SiteSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateSite($site);

            $em->flush();

            $this->addFlash('success', 'flash.site_settings_saved');

            return $this->redirectToRoute('site_settings');
        }

        return $this->render('site/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function trash(TrashRepository $trash): Response {
        return $this->render('site/trash.html.twig', [
            'trash' => $trash->findTrash(),
        ]);
    }
}
