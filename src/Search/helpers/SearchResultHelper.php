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
            // Сначала очистим текст от потенциально опасного содержимого
            $highlightedText = self::sanitizeText($highlightedText);

            $processed = Markdown::process($highlightedText);

            // Исправим возможные проблемы с HTML-тегами
            $processed = self::fixHtmlTags($processed);

            if ($singleLine) {
                $processed = self::convertToSingleLine($processed);
            }

            return $processed;
        }

        return TextProcessor::widget([
            'text' => self::sanitizeText($highlightedText),
        ]);
    }

    /**
     * Fixes unclosed or malformed HTML tags
     * @param string $html
     * @return string
     */
    protected static function fixHtmlTags(string $html): string
    {
        // 1. Создаем конфигурацию
        $config = \HTMLPurifier_Config::createDefault();

        // 2. Настраиваем все параметры ДО финализации конфига
        $config->set('HTML.Allowed', 'p,ol,ul,li,strong,em,a[href],br,mark');
        $config->set('Cache.DefinitionImpl', null);

        $cachePath = Yii::getAlias('@runtime/htmlpurifier');
        if (!file_exists($cachePath)) {
            @mkdir($cachePath, 0777, true);
        }
        $config->set('Cache.SerializerPath', $cachePath);

        // 3. Добавляем кастомные определения
        $config->set('HTML.DefinitionID', 'custom-html-def');
        $config->set('HTML.DefinitionRev', 1);

        // 4. Получаем определение ДО создания экземпляра HTMLPurifier
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('mark', 'Inline', 'Inline', 'Common', array(
                'class' => 'Optional',
            ));
        }

        // 5. Создаем экземпляр HTMLPurifier
        try {
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        } catch (\Exception $e) {
            Yii::error('HTMLPurifier error: ' . $e->getMessage());
            return strip_tags($html, '<p><ol><ul><li><mark><strong><em><a><br>');
        }
    }

    /**
     * Sanitizes text by removing harmful content
     * @param string $text
     * @return string
     */
    protected static function sanitizeText(string $text): string
    {
        // Удаляем HTML комментарии
        $text = preg_replace('/<!--.*?-->/s', '', $text);

        // Удаляем "зеркальные" комментарии HTTrack
        $text = preg_replace('/<!-- Mirrored from.*?-->/is', '', $text);

        // Удаляем другие потенциально опасные конструкции
        $text = preg_replace('/<\?.*?\?>/s', '', $text);
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);

        return $text;
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
