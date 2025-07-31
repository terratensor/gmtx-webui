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
     */
    public function search(SearchForm $form): ParagraphDataProvider
    {
        $results = match ($form->matching) {
            'query_string' => $this->paragraphRepository->findByQueryStringNew($form),
            'match_phrase' => $this->paragraphRepository->findByMatchPhrase($form),
            'match' => $this->paragraphRepository->findByQueryStringMatch($form),
            'context' => $this->paragraphRepository->findByContext($form),
        };


        $responseData = $results->get()->getResponse()->getResponse();
        // Определяем параметры пагинации
        $pagination = $form->matching === 'context' ? [
            'pageSize' => Yii::$app->params['context']['pageSize'],
            'pageSizeLimit' => Yii::$app->params['context']['pageSizeLimit'],
        ] : [
            'pageSize' => Yii::$app->params['searchResults']['pageSize'],
            'pageSizeLimit' => Yii::$app->params['searchResults']['pageSizeLimit'],
        ];
        return new ParagraphDataProvider(
            [
                'query' => $results,
                'pagination' => $pagination,
                'sort' => [
                    'defaultOrder' => [
                        '_score' => SORT_DESC,
                        'chunk' => SORT_ASC,
                        'id' => SORT_ASC,
                    ],
                    'attributes' => [
                        '_score',
                        'chunk',
                        'id',
                    ]
                ],
                'responseData' => $responseData
            ]
        );
    }

    public function facets()
    {
        $comments = $this->paragraphRepository->findFacets();

        $responseData = $comments->get()->getResponse()->getResponse();
        // Определяем параметры пагинации
        $pagination = [
            'pageSize' => Yii::$app->params['searchResults']['pageSize'],
            'pageSizeLimit' => Yii::$app->params['searchResults']['pageSizeLimit'],
        ];
        return new ParagraphDataProvider(
            [
                'query' => $comments,
                'pagination' => $pagination,
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
