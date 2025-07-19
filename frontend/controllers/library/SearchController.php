<?php

declare(strict_types=1);

namespace frontend\controllers\library;


class SearchController extends \yii\web\Controller
{

    private ManticoreService $service;

    public function __construct(
        $id,
        $module,
        ManticoreService $service,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }
}
