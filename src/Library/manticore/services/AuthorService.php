<?php

declare(strict_types=1);

namespace src\Library\manticore\services;

use Manticoresearch\ResultSet;
use src\Library\manticore\repositories\AuthorRepository;
use src\Library\manticore\repositories\ParagraphRepository;

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
        $result = $this->authorRepository->findFacetsByName($value);

        $facets['authors'] = $result;

        return $facets;
    }
}
