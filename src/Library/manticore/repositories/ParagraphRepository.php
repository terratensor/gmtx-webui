<?php

declare(strict_types=1);

namespace src\Library\manticore\repositories;

use Yii;
use Manticoresearch\Table;
use Manticoresearch\Client;
use Manticoresearch\Search;
use Manticoresearch\Query\In;
use Manticoresearch\Response;
use src\Search\forms\SearchForm;
use Manticoresearch\Query\Equals;
use Manticoresearch\Query\BoolQuery;
use src\Search\helpers\SearchHelper;
use Manticoresearch\Query\MatchQuery;
use Manticoresearch\Query\MatchPhrase;
use Manticoresearch\Query\QueryString;
use src\Library\manticore\models\Paragraph;


class ParagraphRepository
{
    private Client $client;
    public Table $table;
    private Search $search;

    private string $indexName = 'library2025';
    public int $pageSize = 20;

    public function __construct(Client $client, $pageSize)
    {

        $this->setIndexName(Yii::$app->params['indexes']['common']);
        $this->client = $client;
        $this->search = new Search($this->client);
        $this->search->setTable($this->indexName);
        $this->pageSize = $pageSize;
    }

    /**
     * @param string $queryString
     * @param string|null $indexName
     * @param SearchForm|null $form
     * @return Search
     * "query_string" accepts an input string as a full-text query in MATCH() syntax
     */
    public function findByQueryStringNew(string $queryString, ?SearchForm $form = null): Search
    {
        // var_dump($this->client);
        $queryString = SearchHelper::processStringWithURLs($queryString);
        $queryString = SearchHelper::escapeUnclosedQuotes($queryString);
        // Экранирует все скобки в строке, если найдена хоть одна непарная.
        $queryString = SearchHelper::escapeUnclosedBrackets($queryString);

        if ($form->genre !== '') {
            if ($form->genre === SearchHelper::EMPTY_GENRE) {
                $this->search->filter('genre', 'equals', "");
            } else {
                $this->search->filter('genre', 'in', $form->genre);
            }
        }

        if ($form->author !== '') {
            if ($form->author === SearchHelper::EMPTY_AUTHOR) {
                $this->search->filter('author', 'equals', "");
            } else {
                $this->search->filter('author', 'in', $form->author);
            }
        }

        if ($form->title !== '') {
            $this->search->filter('title', 'in', $form->title);
        }

        if ($form->query !== '') {
            // Проверяем, содержит ли запрос какие-либо из поисковых конструкций. Field search operator @author, @title, @genre, @content
            $hasFieldSearchOperator = preg_match('/@(title|author|genre|content)\b/', $queryString);

            // Добавляем @content только если нет других директив
            if (!$hasFieldSearchOperator) {
                $queryString = "@content {$queryString}";
            }
        }

        // Выполняем поиск если установлен фильтр или установлена строка поиска
        $search = $this->search->search($queryString);
        // Подготавливаем фасеты
        $this->prepareFacets($search);

        // Включаем нечёткий поиск, если строка не пустая или не содержит символы, используемые в полнотекстовом поиске
        // и не содержит hash автварки пользователя
        if ($form->fuzzy) {
            Yii::$app->session->setFlash('success', "Включена опция «Нечёткий поиск». Для выключения уберите флажок в настройках поиска.");
            static::applyFuzzy($search, true);
        }

        // Если нет совпадений no_match_size возвращает пустое поле для подсветки
        $search->highlight(
            ['content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ],
        );

        // var_dump($search->getClient());

        return $search;
    }

    public function findAggsAll(SearchForm $form): array|Response
    {
        $search = $this->client->search([
            'body' => [
                'table' => 'library2025',
                'query' => [
                    'match_all' => '',
                ],
                'aggs' => [
                    'genre_group' => [
                        'terms' => [
                            'field' => 'genre',
                            'size' => 200
                        ],
                        // "sort" => [
                        //     [
                        //         "count(*)" => [
                        //             "order" => "desc"
                        //         ]
                        //     ]
                        // ]
                    ],
                    'author_group' => [
                        'terms' => [
                            'field' => 'author',
                            'size' => 200
                        ]
                    ],
                    'title_group' => [
                        'terms' => [
                            'field' => 'title',
                            'size' => 200
                        ]
                    ]
                ],
                'limit' => 1,
            ]
        ], true);

        return $search;
    }

    public function matchAll()
    {
        $search = $this->client->search(['body' => ['table' => 'library2025', 'query' => ['match_all' => '']]], true);
        return $search;
    }

    /**
     * @param string $queryString
     * @param string|null $indexName
     * @param SearchForm|null $form
     * @return Search
     * "match" is a simple query that matches the specified keywords in the specified fields.
     */
    public function findByQueryStringMatch(
        string $queryString,
        ?SearchForm $form = null
    ): Search {

        if ($form->genre !== '') {
            if ($form->genre === SearchHelper::EMPTY_GENRE) {
                $this->search->filter('genre', 'equals', "");
            } else {
                $this->search->filter('genre', 'in', $form->genre);
            }
        }

        if ($form->author !== '') {
            if ($form->author === SearchHelper::EMPTY_AUTHOR) {
                $this->search->filter('author', 'equals', '');
            } else {
                $this->search->filter('author', 'in', $form->author);
            }
        }

        if ($form->title !== '') {
            $this->search->filter('title', 'in', $form->title);
        }

        if ($form->query !== '') {
            $search = $this->search->match($form->query, 'content');
        } else {
            $search = $this->search->search($form->query);
        }

        // Подготавливаем фасеты
        $this->prepareFacets($search);

        $search->highlight(
            ['title', 'content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ]
        );

        return $search;
    }

    /**
     * @param string $queryString
     * @param string|null $indexName
     * @return Search
     * "match_phrase" is a query that matches the entire phrase. It is similar to a phrase operator in SQL.
     */
    public function findByMatchPhrase(
        string $queryString,
        ?SearchForm $form = null
    ): Search {

        $query = new BoolQuery();

        if ($form->genre !== '') {
            if ($form->genre === SearchHelper::EMPTY_GENRE) {
                $query->should(new In('genre', ['']));
            } else {
                $query->should(new In('genre', [$form->genre]));
            }
        }

        if ($form->author !== '') {
            if ($form->author === SearchHelper::EMPTY_AUTHOR) {
                $query->should(new In('author', ['']));
            } else {
                $query->should(new In('author', [$form->author]));
            }
        }

        if ($form->title !== '') {
            $query->should(new In('title', [$form->title]));
        }

        if ($form->query !== '') {
            $query->must(new MatchPhrase($form->query, 'content'));
        } else {
            $query->must(new QueryString($queryString));
        }

        $search = $this->search->search($query);

        // Подготавливаем фасеты
        $this->prepareFacets($search);

        $search->highlight(
            ['title', 'content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ]
        );

        return $search;
    }

    /**
     * @param SearchForm $form
     * @param string|null $indexName
     * @return Search
     * "match" is a query that matches the entire phrase. It is similar to a phrase operator in SQL.
     * The search is carried out by genre, author, title
     */
    public function findByContext(SearchForm $form, ?string $indexName = null): Search
    {
        $this->search->reset();
        if ($indexName) {
            // $this->setIndex($this->client->index($indexName));
        }

        // Запрос переделан под фильтр
        $query = new BoolQuery();

        $query->must(new Equals('source_uuid', $form->source_uuid));

        $search = $this->search->search($query);
        $search->facet('source_uuid');

        $search->highlight(
            ['genre', 'author', 'title', 'content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ]
        );

        return $search;
    }

    /**
     * @param $queryString String Число или строка чисел через запятую
     * @param string|null $indexName
     * @return Search
     * Поиск по data_id, вопрос или комментарий, число или массив data_id
     */
    public function findByParagraphId(
        string $queryString,
        ?string $indexName = null,
        ?SearchForm $form = null
    ): Search {
        $this->search->reset();
        if ($indexName) {
            // $this->setIndex($this->client->index($indexName));
        }

        $result = explode(',', $queryString);

        foreach ($result as $key => $item) {
            $item = (int)$item;
            if ($item == 0) {
                unset($result[$key]);
                continue;
            }
            $result[$key] = $item;
        }
        // Запрос переделан под фильтр
        $query = new BoolQuery();

        if (!empty($result)) {
            $query->must(new In('id', array_values($result)));
        } else {
            throw new \DomainException('Неправильный запрос, при поиске по номеру(ам) надо указать номер параграфа, или перечислить номера через запятую');
        }

        // Выполняем поиск если установлен фильтр или установлен строка поиска
        if ($form->query) {
            $search = $this->search->search($query);
        } else {
            throw new \DomainException('Задан пустой поисковый запрос');
        }

        $search->highlight(
            ['content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ]
        );
        $search->sort('id', 'asc');

        return $search;
    }

    public function findBookById(int $id): \Manticoresearch\ResultSet
    {
        $this->search->reset();

        $search = $this->search->setTable($this->indexName);

        $search->search('');
        $search->filter('book_id', $id);
        $search->limit(1);

        return $search->get();
    }

    public function findParagraphsByBookId(int $id): Search
    {
        $this->search->reset();

        $search = $this->search->setTable($this->indexName);


        $search->filter('book_id', 'in', $id);

        // Запрос переделан под фильтр
        $query = new BoolQuery();


        $query->must(new In('id', $id));
        $this->search->search($query);

        $search->highlight(
            ['content'],
            [
                'limit' => 0,
                'no_match_size' => 0,
                'pre_tags' => '<mark>',
                'post_tags' => '</mark>'
            ]
        );
        return $search;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
    }

    /**
     * Возвращает paragraph по uuid
     * @deprecated
     * @param string $uuid
     * @return Paragraph
     */
    public function findByParagraphUuid(string $uuid): Paragraph
    {

        $this->search->reset();

        $query = new BoolQuery();

        $query->must(new Equals('uuid', $uuid));

        $search = $this->search->search($query);

        $current = $search->get()->current();
        $paragraph = new Paragraph($current->getData());
        $paragraph->setId((int)$current->getId());
        return $paragraph;
    }

    /**
     * Возвращает paragraph по id
     * @param string $uuid
     * @return Paragraph
     */
    public function getByParagraphID(string $id): Paragraph
    {
        $table =  new Table($this->client, 'library2025');
        /** @var \Manticoresearch\ResultHit **/
        $hit = $table->getDocumentById($id);

        if (!$hit) {
            throw new \DomainException('Параграф с не найден');
        }

        $par = new Paragraph($hit->getData());
        $par->setId((int)$hit->getId());

        return $par;
    }

    /**
     * @param Search $search
     * @param bool $enable_layouts
     * @return void
     */
    protected static function applyFuzzy(Search $search, bool $enable_layouts = false): void
    {
        $search->option('fuzzy', 1);
        $layouts = [];
        if ($enable_layouts) {
            $layouts = ['ru', 'us'];
        }
        $search->option('layouts', $layouts);
    }

    /**
     * @param Search $search
     * @param int $limit
     * @param string $sortField
     * @param string $sortDirection
     */
    private function prepareFacets(Search $search, int $limit = 200, string $sortField = 'count(*)', string $sortDirection = 'desc'): void
    {
        $search->facet('genre', 'genre_group', $limit, $sortField, $sortDirection);
        $search->facet('author', 'author_group', $limit, $sortField, $sortDirection);
        $search->facet('title', 'title_group', $limit, $sortField, $sortDirection);
    }
}
