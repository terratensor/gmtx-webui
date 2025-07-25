<?php

declare(strict_types=1);

namespace src\Search\helpers;

use Yii;
use yii\helpers\Markdown;
use src\Library\manticore\models\Paragraph;

class SearchResultHelper
{
    /**
     * Returns a highlighted version of the given field from a search result's paragraph.
     * @param Paragraph $paragraph
     * @param string $field
     * @param string $type
     * @param bool $singleLine
     * @return string
     */
    public static function highlightFieldContent(Paragraph $paragraph, string $field, string $type = 'text', bool $singleLine = false): string
    {
        $highlight = $paragraph->highlight[$field] ?? [];
        $highlightedText = $highlight[0] ?? $paragraph->{$field};

        if ($type === 'markdown') {
            $processed = Markdown::process($highlightedText);

            if ($singleLine) {
                // Сохраняем теги <mark> при удалении переносов строк
                $processed = self::convertToSingleLine($processed);
            }

            return $processed;
        }

        return TextProcessor::widget([
            'text' => $highlightedText,
        ]);
    }

    /**
     * Converts text to single line while preserving <mark> tags
     * @param string $text
     * @return string
     */
    protected static function convertToSingleLine(string $text): string
    {
        // Удаляем все HTML теги кроме <mark>
        $text = strip_tags($text, '<mark>');

        // Заменяем множественные пробелы и переносы на один пробел
        $text = preg_replace('/\s+/u', ' ', $text);

        // Удаляем пробелы перед и после тегов <mark>
        $text = preg_replace('/\s*<\/?mark>\s*/u', '$0', $text);

        return trim($text);
    }

    public static function fieldContent(Paragraph $paragraph, string $field): string
    {
        return $paragraph->{$field};
    }

    // src/helpers/SearchHelper.php

    public static function getResetFiltersUrl(): array
    {
        $request = Yii::$app->request;
        $params = $request->queryParams;

        // Удаляем все фильтры, кроме поискового запроса
        if (isset($params['search']['query'])) {
            $params['search'] = ['query' => $params['search']['query']];
        } else {
            unset($params['search']);
        }

        // Сбрасываем пагинацию
        unset($params['page']);

        return array_merge(['site/index'], $params);
    }
}
