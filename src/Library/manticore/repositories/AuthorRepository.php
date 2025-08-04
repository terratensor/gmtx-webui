<?php

declare(strict_types=1);

namespace src\Library\manticore\repositories;

use Manticoresearch\Table;
use Manticoresearch\Client;
use Manticoresearch\Search;

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

    public function findFacetsByName($value): array
    {
        $result = [];
        // Получаем количество категорий из таблицы categories
        if ($value == '') {
            $query_count = "SELECT COUNT(*) FROM authors";
        } else {            
            $query_count = "SELECT COUNT(*) FROM authors WHERE MATCH('@name ^$value*')";
        }
        $response = $this->client->sql($query_count, true);
        $limit = $response[0] ?? 100;

        // var_dump($limit);

        $result['count'] = $limit;

        $this->search->search("@author ^$value*");
        $this->search->setSource(['id', 'name']);
        $this->search->facet('author', 'author_group', 100, 'count(*)', 'desc');
        $this->search->limit($this->pageSize);

        $result['data'] = $this->search->get()->getFacets();
        
        return $result;
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
