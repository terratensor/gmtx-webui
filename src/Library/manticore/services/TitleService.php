<?php

declare(strict_types=1);

namespace src\Library\manticore\services;


use src\Library\manticore\repositories\TitleRepository;

class TitleService
{
    private TitleRepository $titleRepository;
    public function __construct(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }

    public function findTitle($value)
    {
        $facets = [];
        $result = $this->titleRepository->findFacetsByName($value);

        $facets['titles'] = $result;

        return $facets;
    }
}
