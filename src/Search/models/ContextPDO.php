<?php

declare(strict_types=1);

namespace src\Search\models;

use src\Search\forms\SearchForm;
use src\Library\manticore\models\Paragraph;

class ContextPDO
{
    public string $bookName;
    public SearchForm $searchForm;
    public Paragraph $paragraph;

    public function __construct(string $bookName, SearchForm $searchForm, Paragraph $paragraph) {
        $this->bookName = $bookName;
        $this->searchForm = $searchForm;
        $this->paragraph = $paragraph;
    }
}
