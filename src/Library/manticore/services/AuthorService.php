<?php

declare(strict_types=1);

namespace src\Library\manticore\services;


use src\Library\manticore\repositories\AuthorRepository;

class AuthorService
{
    private $authorRepository;
    public function __construct(AuthorRepository $authorRepository)
    {
        $this->authorRepository = $authorRepository;
    }

    public function findAuthor($value)
    {
        $facets = [];
        $result = $this->authorRepository->findFacetsByName($value, $genre = null, $title = null);

        $facets['authors'] = $result;

        return $facets;
    }
}
