<?php

declare(strict_types=1);

namespace src\Library\manticore\services;

use src\Search\forms\SearchForm;
use src\Search\models\ContextPDO;
use src\Library\manticore\helpers\SourceHelper;
use src\Library\manticore\repositories\ParagraphRepository;

class ContextService
{
    private ParagraphRepository $paragraphRepository;

    public function __construct(ParagraphRepository $paragraphRepository)
    {
        $this->paragraphRepository = $paragraphRepository;
    }

    public function handle(string $id): ContextPDO
    {
        try {
            $paragraph = $this->paragraphRepository->getByParagraphID($id);
        } catch (\Exception $e) {
            throw new \DomainException($e->getMessage());
        }
        $form = new SearchForm();
        $form->matching = 'context';
        $form->genre = $paragraph->genre;
        $form->author = $paragraph->author;
        $form->title = $paragraph->title;
        $form->source_uuid = $paragraph->source_uuid;

        return new ContextPDO(SourceHelper::fullName($paragraph), $form, $paragraph);
    }
}