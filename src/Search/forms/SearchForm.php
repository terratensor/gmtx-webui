<?php

declare(strict_types=1);

namespace src\Search\forms;

use yii\base\Model;
use src\Search\helpers\SearchHelper;

class SearchForm extends Model
{
    public string $query = '';
    public $paragraphId = null;
    public string $genre = '';
    public string $author = '';
    public string $title = '';
    public string $text = '';
    public string $source_uuid = '';
    public bool $singleLineMode = false;
    public bool $genreInlineView = false;
    // Включает нечёткий поиск 
    public bool $fuzzy = false;
    public string $matching = 'query_string';   
    
    public string $model = 'glove';

    public function rules(): array
    {
        return [
            ['query', 'string'],
             ['paragraphId', 'integer', 'min' => 1],
            ['genre', 'string'],
            ['author', 'string'],
            ['title', 'string'],
            ['text', 'string'],
            ['source_uuid', 'string'],
            ['matching', 'in', 'range' => array_keys($this->getMatching())],
            [['singleLineMode', 'fuzzy', 'genreInlineView'], 'boolean'],
            ['model', 'in', 'range' => ['glove', 'e5-small']],
        ];
    }

    public function getMatching(): array
    {
        return [
            'query_string' => 'Обычный поиск',
            'match_phrase' => 'Точное соответствие',
            'match' => 'Любое слово',
            'vector' => 'Векторное соответствие',
        ];
    }

    public function formName(): string
    {
        return 'search';
    }

    public function beforeValidate(): bool
    {
        // Нормализуем поисковый запрос, удаляем лишние пробелы, url запроса остается без изменения, дл
        // но при следующей отправке нормализованного запроса будет уже обновленный url
        $this->query = SearchHelper::normalizeString($this->query, false);

        return parent::beforeValidate();
    }

    public function isEmpty(): bool
    {
        return (($this->query === '' && ($this->genre === '' && $this->author === '' & $this->title === '')) && $this->paragraphId === null);
    }
}
