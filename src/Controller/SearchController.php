<?php

namespace App\Controller;

use App\Repository\SearchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SearchController extends AbstractController {
    public function search(Request $request, SearchRepository $search): Response {
        $searchOptions = $search->parseRequest($request);

        if ($searchOptions) {
            $results = $search->search($searchOptions);
        }

        return $this->render('search/results.html.twig', [
            'query' => $searchOptions['query'] ?? null,
            'results' => $results ?? [],
        ]);
    }

    public function openSearchDescription(): Response {
        $response = new Response();
        $response->headers->set(
            'Content-Type',
            'application/opensearchdescription+xml; charset=UTF-8'
        );
        $response->setPublic();
        $response->setSharedMaxAge(86400);

        return $this->render('search/opensearch.xml.twig', [], $response);
    }
}
