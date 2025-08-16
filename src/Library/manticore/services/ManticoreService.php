<?php

declare(strict_types=1);

namespace src\Library\manticore\services;

use Yii;
use yii\caching\TagDependency;
use yii\data\ArrayDataProvider;
use src\Search\forms\SearchForm;
use yii\data\DataProviderInterface;
use src\Library\manticore\models\Paragraph;
use App\Library\manticore\services\VectorizerService;
use src\Library\manticore\repositories\TitleRepository;
use src\Library\manticore\repositories\AuthorRepository;
use src\Library\manticore\repositories\ParagraphRepository;
use src\Library\manticore\repositories\ParagraphDataProvider;

class ManticoreService
{
    private ParagraphRepository $paragraphRepository;
    private AuthorRepository $authorRepository;
    private TitleRepository $titleRepository;
    private VectorizerService $vectorizer;

    public function __construct(
        ParagraphRepository $questionRepository,
        AuthorRepository $authorRepository,
        TitleRepository $titleRepository,
        VectorizerService $vectorizer
    ) {
        $this->paragraphRepository = $questionRepository;
        $this->authorRepository = $authorRepository;
        $this->titleRepository = $titleRepository;
        $this->vectorizer = $vectorizer;
    }

    /**
     * @param SearchForm $form
     * @return ParagraphDataProvider
     */
    public function search(SearchForm $form, ?string $similarity_model): ParagraphDataProvider
    {
        // Обработка поиска по похожим параграфам
        if ($form->paragraphId && $form->matching === 'vector') {
            $results = $this->paragraphRepository->findBySimilarParagraphId(
                (int)$form->paragraphId,
                $form,
                $similarity_model
            );
        }
        // Обычный поиск
        else {
            $results = match ($form->matching) {
                'query_string' => $this->paragraphRepository->findByQueryStringNew($form),
                'match_phrase' => $this->paragraphRepository->findByMatchPhrase($form),
                'match' => $this->paragraphRepository->findByQueryStringMatch($form),
                'vector' => $this->paragraphRepository->findByVector(
                    $form,
                    $this->vectorizer->vectorize($form->query, $form->model)
                ),
                'context' => $this->paragraphRepository->findByContext($form),
            };
        }
        // var_dump($results->get()->getResponse()->getResponse());
        $responseData = $results->get()->getResponse()->getResponse();
        // var_dump($responseData);
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
                    // 'defaultOrder' => [
                    //     '_score' => SORT_DESC,
                    //     'chunk' => SORT_ASC,
                    //     'id' => SORT_ASC,
                    // ],
                    // 'attributes' => [
                    //     '_score',
                    //     'chunk',
                    //     'id',
                    // ]
                ],
                'responseData' => $responseData
            ]
        );
    }

    public function facets(): array
    {
        $cacheKey = __METHOD__ . '_' . md5('index');
        $cacheDuration = 3600; // 1 час
        return Yii::$app->cache->getOrSet(
            $cacheKey,
            function () {
                $facets = [];
                $facets['total_count'] = $this->paragraphRepository->getTotalCount(true);
                $facets['genres'] = $this->paragraphRepository->findGenreFacets();
                $facets['authors'] = $this->authorRepository->findFacetsByName('');
                $facets['titles'] = $this->titleRepository->findFacetsByName('');
                return $facets;
            },
            $cacheDuration,
            new TagDependency(['tags' => 'index'])
        );
    }

    // public function aggs(SearchForm $form)
    // {
    //     $resp = $this->paragraphRepository->findAggsAll($form);
    //     return $resp->getResponse();
    // }

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
