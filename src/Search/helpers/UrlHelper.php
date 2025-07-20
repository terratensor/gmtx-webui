<?php

namespace src\Search\helpers;

use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class UrlHelper
{
    /**
     * Генерирует URL с добавлением/обновлением параметра поиска
     * 
     * @param string $paramName Название параметра (например, 'genre', 'author')
     * @param string $paramValue Значение параметра
     * @param string $route Маршрут (по умолчанию 'site/search')
     * @return string Сформированный URL
     */
    public static function addSearchParam($paramName, $paramValue, $route = 'site/search')
    {
        // Получаем текущие параметры запроса
        $currentParams = \Yii::$app->request->get();
        $searchParams = $currentParams['search'] ?? [];

        // Добавляем/обновляем параметр
        $newSearchParams = ArrayHelper::merge($searchParams, [$paramName => $paramValue]);
        $newParams = ArrayHelper::merge($currentParams, ['search' => $newSearchParams]);

        // Создаем URL с новыми параметрами
        return Url::to(array_merge([$route], $newParams));
    }
}
