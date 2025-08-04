<?php

declare(strict_types=1);

namespace src\Library\manticore\repositories;

use Yii;
use Manticoresearch\Table;
use Manticoresearch\Client;
use Manticoresearch\Search;
use yii\caching\TagDependency;

class AuthorRepository
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

    public function findByName($value)
    {
        $this->search->search("^$value*");
        $this->search->setSource(['id', 'name']);
        $this->search->limit($this->pageSize);

        return $this->search->get();
    }

    public function findFacetsByName(string $value, ?string $genre = null, ?string $title = null): array
    {
        $cacheKey = __METHOD__ . '_' . md5($value);
        $cacheDuration = 3600; // 1 час

        return Yii::$app->cache->getOrSet(
            $cacheKey,
            function () use ($value, $genre, $title) {
                $result = [];
                // Получаем количество категорий из таблицы categories
                if ($value == '') {
                    $query_count = "SELECT COUNT(*) FROM authors";
                } else {
                    $query_count = "SELECT COUNT(*) FROM authors WHERE MATCH('@name $value')";
                }
                $response = $this->client->sql($query_count, true);
                $limit = $response[0] ?? 100;

                $result['count'] = $limit;

                $result['count'] = $limit;
                if ($value !== '') {
                    $value = "@author $value";
                }
                $this->search->search($value);
                if ($genre !== null) {
                    $this->search->filter('genre', 'in', $genre);
                }
                if ($title !== null) {
                    $this->search->filter('author', 'in', $title);
                }
                $this->search->setSource(['id', 'name']);
                $this->search->facet('author', 'author_group', 100, 'count(*)', 'desc');
                $this->search->limit(0);

                $result['data'] = $this->search->get()->getFacets();

                return $result;
            },
            $cacheDuration,
            new TagDependency(['tags' => 'authors'])
        );
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
        $search->option('distance', 2);
    }
}
