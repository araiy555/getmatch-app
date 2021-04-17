<?php
/** @noinspection PhpUnusedParameterInspection */

namespace App\Controller;

use App\DataObject\CssThemeData;
use App\Entity\CssTheme;
use App\Entity\CssThemeRevision;
use App\Entity\Theme;
use App\Form\CssThemeType;
use App\Repository\BundledThemeRepository;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends AbstractController {
    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function themes(
        ThemeRepository $themes,
        BundledThemeRepository $bundledThemes,
        int $page,
        array $themesConfig
    ): Response {
        return $this->render('theme/themes.html.twig', [
            'default' => $themesConfig['_default'],
            'themes' => $themes->findPaginated($page),
            'to_create' => $bundledThemes->findThemesToCreate(),
            'to_remove' => $bundledThemes->findThemesToRemove(),
        ]);
    }

    /**
     * @Entity("theme", expr="repository.findOneById(themeId)")
     * @Entity("revision", expr="repository.findOneByThemeAndId(theme, revisionId)")
     */
    public function css(CssTheme $theme, CssThemeRevision $revision): Response {
        $response = new Response($revision->getCss(), 200, [
            'Content-Type' => 'text/css',
        ]);
        $response->setPublic();
        $response->setImmutable(true);

        return $response;
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function createCss(EntityManagerInterface $em, Request $request): Response {
        $data = new CssThemeData();
        $form = $this->createForm(CssThemeType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $theme = new CssTheme($data->name, $data->css);

            $em->persist($theme);
            $em->flush();

            return $this->redirectToRoute('themes');
        }

        return $this->render('theme/create_css.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function editCss(CssTheme $theme, EntityManagerInterface $em, Request $request): Response {
        $data = CssThemeData::fromTheme($theme);
        $form = $this->createForm(CssThemeType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->updateTheme($theme);
            $em->flush();

            return $this->redirectToRoute('themes');
        }

        return $this->render('theme/edit_css.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function delete(Theme $theme, EntityManagerInterface $em, Request $request): Response {
        $this->validateCsrf('delete_theme', $request->request->get('token'));

        $em->remove($theme);
        $em->flush();

        return $this->redirectToRoute('themes');
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("ROLE_ADMIN", statusCode=403)
     */
    public function sync(Request $request, BundledThemeRepository $themes, EntityManagerInterface $em): Response {
        $this->validateCsrf('sync_themes', $request->request->get('token'));

        foreach ($themes->findThemesToCreate() as $theme) {
            $em->persist($theme);
        }

        foreach ($themes->findThemesToRemove() as $theme) {
            $em->remove($theme);
        }

        $em->flush();

        return $this->redirectToRoute('themes');
    }
}
