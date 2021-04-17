<?php

namespace App\Controller;

use App\Entity\User;
use App\Utils\UrlMetadataFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Helpers for Ajax-related stuff.
 */
final class AjaxController extends AbstractController {
    /**
     * @var UrlMetadataFetcherInterface
     */
    private $metadataFetcher;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        UrlMetadataFetcherInterface $metadataFetcher,
        ValidatorInterface $validator
    ) {
        $this->metadataFetcher = $metadataFetcher;
        $this->validator = $validator;
    }

    /**
     * JSON action for retrieving link titles.
     *
     * - 200 - Found a title
     * - 400 - Bad URL
     * - 404 - No title found
     *
     * @IsGranted("ROLE_USER")
     */
    public function fetchTitle(Request $request): Response {
        $url = $request->request->get('url');
        $errors = $this->validator->validate($url, [new NotBlank(), new Url()]);

        if (\count($errors) > 0) {
            throw new BadRequestHttpException();
        }

        $title = $this->metadataFetcher->fetchTitle($url);

        if ($title === null) {
            throw new NotFoundHttpException();
        }

        return $this->json(['title' => $title]);
    }

    public function userPopper(User $user): Response {
        return $this->render('ajax/user_popper.html.twig', [
            'user' => $user,
        ]);
    }
}
