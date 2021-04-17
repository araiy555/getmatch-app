<?php

namespace App\Controller;

use App\DataObject\BadPhraseData;
use App\Entity\BadPhrase;
use App\Form\BadPhraseSearchType;
use App\Form\BadPhraseType;
use App\Repository\BadPhraseRepository;
use App\Utils\BadPhraseMatcher;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface as Validator;

/**
 * @IsGranted("ROLE_USER")
 * @IsGranted("ROLE_ADMIN", statusCode=403)
 */
final class BadPhraseController extends AbstractController {
    public function list(BadPhraseRepository $badPhrases, int $page): Response {
        return $this->render('bad_phrase/list.html.twig', [
            'bad_phrases' => $badPhrases->findPaginated($page),
        ]);
    }

    public function renderForm(): Response {
        $form = $this->createForm(BadPhraseType::class, null, [
            'action' => $this->generateUrl('bad_phrase_add'),
        ]);

        return $this->render('bad_phrase/_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function add(Request $request, EntityManager $em): Response {
        $data = new BadPhraseData();
        $form = $this->createForm(BadPhraseType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($data->toBadPhrase());
            $em->flush();

            return $this->redirectToRoute('bad_phrase_list');
        }

        return $this->render('bad_phrase/form_errors.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function search(BadPhraseMatcher $matcher, Request $request): Response {
        $form = $this->createForm(BadPhraseSearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $query = $form->getData()['query'];
            $matches = isset($query) ? $matcher->findMatching($query) : null;
        }

        return $this->render('bad_phrase/search.html.twig', [
            'bad_phrases' => $matches ?? null,
            'form' => $form->createView(),
        ]);
    }

    public function renderSearchForm(): Response {
        $form = $this->createForm(BadPhraseSearchType::class);

        return $this->render('bad_phrase/_search_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function remove(Request $request, EntityManager $em, Validator $validator): Response {
        $this->validateCsrf('remove_bad_phrase', $request->request->get('token'));
        $ids = (array) $request->request->get('remove_bad_phrase');

        $errors = $validator->validate($ids, new Assert\All([
            new Assert\NotBlank(),
            new Assert\Uuid(['strict' => false]),
        ]));

        if (\count($errors) > 0) {
            throw new BadRequestHttpException('Invalid UUID');
        }

        $em->transactional(static function () use ($em, $ids): void {
            foreach ($ids as $id) {
                $entity = $em->find(BadPhrase::class, Uuid::fromString($id));

                if ($entity) {
                    $em->remove($entity);
                }
            }
        });

        return $this->redirectToRoute('bad_phrase_list');
    }
}
