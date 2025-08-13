<?php

namespace frontend\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class SimilarParagraphWidget extends Widget
{
    public $paragraphId;
    public $currentParams = [];
    public $currentModel;
    public $modelsList = [
        'glove' => 'GloVe',
        'e5-small' => 'E5 Small'
    ];

    public function init()
    {
        parent::init();

        // Приоритет: GET → localStorage (JS) → сессия → дефолт
        $this->currentModel = $this->currentModel ??
            Yii::$app->request->get('model', Yii::$app->session->get('similar_model', 'glove'));

        Yii::$app->session->set('similar_model', $this->currentModel);

        // Формируем параметры ссылки
        $params = $this->currentParams;
        $params['search']['paragraphId'] = $this->paragraphId;
        $params['search']['matching'] = 'vector';
        $params['model'] = $this->currentModel;
        unset($params['search']['query'], $params['page']);

        $this->currentParams = $params;
    }

    public function run()
    {
        $similarUrl = Url::to(array_merge(['library/search'], $this->currentParams));

        $html = Html::tag(
            'div',
            Html::a(
                '<i class="bi bi-intersect"></i> Похожие (' . Html::encode($this->modelsList[$this->currentModel]) . ')',
                $similarUrl,
                [
                    'class' => 'btn btn-link btn-context paragraph-similar',
                    'data-paragraph-id' => $this->paragraphId,
                    'data-model' => $this->currentModel,
                    'target' => '_blank',
                    'style' => 'text-decoration: none;'
                ]
            )
                .
                Html::button('', [
                    'class' => 'btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split',
                    'data-bs-toggle' => 'dropdown',
                    'aria-expanded' => 'false'
                ])
                .
                Html::tag(
                    'ul',
                    implode('', array_map(function ($key, $label) {
                        return Html::tag(
                            'li',
                            Html::a($label, '#', [
                                'class' => 'dropdown-item choose-model',
                                'data-model' => $key
                            ])
                        );
                    }, array_keys($this->modelsList), $this->modelsList)),
                    ['class' => 'dropdown-menu']
                ),
            ['class' => 'dropdown d-inline similar-dropdown']
        );

        // Встраиваем JS прямо в виджет
        $js = <<<JS
(function(){
    // Восстановление модели из localStorage, если нет в GET
    let savedModel = localStorage.getItem('similar_model');
    if (savedModel) {
        // Обновляем все ссылки на странице
        document.querySelectorAll('.paragraph-similar').forEach(function(el){
            let href = new URL(el.getAttribute('href'), window.location.origin);
            href.searchParams.set('model', savedModel);
            el.setAttribute('href', href.toString());
            el.setAttribute('data-model', savedModel);
            el.innerHTML = '<i class="bi bi-intersect"></i> Похожие (' + (savedModel === 'glove' ? 'GloVe' : 'E5 Small') + ')';
        });
    }

    // Обработка выбора модели
    document.addEventListener('click', function(e) {
        if (e.target.closest('.choose-model')) {
            e.preventDefault();
            let model = e.target.closest('.choose-model').dataset.model;
            localStorage.setItem('similar_model', model);

            document.querySelectorAll('.paragraph-similar').forEach(function(el){
                let href = new URL(el.getAttribute('href'), window.location.origin);
                href.searchParams.set('model', model);
                el.setAttribute('href', href.toString());
                el.setAttribute('data-model', model);
                el.innerHTML = '<i class="bi bi-intersect"></i> Похожие (' + (model === 'glove' ? 'GloVe' : 'E5 Small') + ')';
            });
        }
    });
})();
JS;

        $this->view->registerJs($js);

        return $html;
    }
}
