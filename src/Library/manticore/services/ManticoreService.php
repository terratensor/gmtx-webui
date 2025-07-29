<?php

declare(strict_types=1);

namespace src\Library\manticore\services;

use Yii;
use src\Search\forms\SearchForm;
use src\Library\manticore\repositories\ParagraphRepository;
use src\Library\manticore\repositories\ParagraphDataProvider;

class ManticoreService
{
    private ParagraphRepository $paragraphRepository;

    public function __construct(ParagraphRepository $questionRepository)
    {
        $this->paragraphRepository = $questionRepository;
    }

    /**
     * @param SearchForm $form
     * @return ParagraphDataProvider
     * @throws EmptySearchRequestExceptions
     */
    public function search(SearchForm $form): ParagraphDataProvider
    {
        $queryString = $form->query;

        $comments = match ($form->matching) {
            'query_string' => $this->paragraphRepository->findByQueryStringNew($queryString, $form),
            'match_phrase' => $this->paragraphRepository->findByMatchPhrase($queryString, $form),
            'match' => $this->paragraphRepository->findByQueryStringMatch($queryString,  $form),
        };


        $responseData = $comments->get()->getResponse()->getResponse();

        return new ParagraphDataProvider(
            [
                'query' => $comments,
                'pagination' => [
                    'pageSize' => Yii::$app->params['searchResults']['pageSize'],
                    'pageSizeLimit' => Yii::$app->params['searchResults']['pageSizeLimit'],
                ],
                'sort' => [
                    //                 'defaultOrder' => [
                    //     'id' => SORT_ASC,
                    //     'chunk' => SORT_ASC,
                    // ],
                    'attributes' => [
                        'id',
                        'chunk',
                    ]
                ],
                'responseData' => $responseData
            ]
        );
    }

    public function aggs(SearchForm $form)
    {
        $resp = $this->paragraphRepository->findAggsAll($form);
        return $resp->getResponse();
    }

    public function findByBook(int $id): ParagraphDataProvider
    {
        $paragraphs = $this->paragraphRepository->findParagraphsByBookId($id);

        return new ParagraphDataProvider(
            [
                'query' => $paragraphs,
                'pagination' => [
                    'pageSize' => Yii::$app->params['searchResults']['pageSize'],
                ],
                'sort' => [
                    'defaultOrder' => [
                        'id' => SORT_ASC,
                        'chunk' => SORT_ASC,
                    ],
                    'attributes' => [
                        'id',
                        'chunk'
                    ]
                ],
            ]
        );
    }

    public function findBook($id): \Manticoresearch\ResultSet
    {
        return $this->paragraphRepository->findBookById((int)$id);
    }
}
