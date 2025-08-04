<?php

declare(strict_types=1);

namespace src\Library\manticore\repositories;

use Yii;
use Manticoresearch\Table;
use Manticoresearch\Client;
use Manticoresearch\Search;
use yii\caching\TagDependency;

class TitleRepository
{
    private Client $client;
    public Table $table;
    private Search $search;

    private string $indexName = 'library2025';
    public int $pageSize = 20;

    public function __construct(Client $client, $pageSize)
    {
        $this->client = $client;
        $this->search = new Search($this->client);
        $this->search->setTable($this->indexName);
        if ($pageSize) {
            $this->pageSize = $pageSize;
        }
    }

    public function findFacetsByName(string $value): array
    {
        $cacheKey = __METHOD__ . '_' . md5($value);
        $cacheDuration = 3600; // 1 час

        return Yii::$app->cache->getOrSet(
            $cacheKey,
            function () use ($value) {
                $result = [];
                // Получаем количество категорий из таблицы categories
                if ($value == '' && strlen($value) < 2) {
                    $query_count = "SELECT COUNT(*) FROM titles";
                } else {
                    $query_count = "SELECT COUNT(*) FROM titles WHERE MATCH('@title ^$value*')";
                }
                $response = $this->client->sql($query_count, true);
                $limit = $response[0] ?? 100;

                $result['count'] = $limit;
                if ($value !== '') {
                    $value = "@title $value";
                }
                $this->search->search("$value");
                $this->search->setSource(['id', 'name']);
                $this->search->facet('title', 'title_group', 100, 'count(*)', 'desc');
                $this->search->limit(0);

                $result['data'] = $this->search->get()->getFacets();

                return $result;
            },
            $cacheDuration,
            new TagDependency(['tags' => 'titles'])
        );
    }
}
