<?php

declare(strict_types=1);

use yii\data\Pagination;
use yii\bootstrap5\LinkPager;
use frontend\widgets\ScrollWidget;
use src\Search\helpers\SearchResultHelper;
use src\Library\manticore\models\Paragraph;
use src\Library\manticore\repositories\ParagraphDataProvider;


/** @var yii\web\View $this */
/** @var ParagraphDataProvider $results */

/** @var string $bookName */

$this->title = $bookName;
$fragment = Yii::$app->request->get()['f'] ?? 0;

?>
<div class="container-fluid quote-results">

    <?php
    // Property totalCount пусто пока не вызваны данные модели getModels(),
    // сначала получаем массив моделей, потом получаем общее их количество
    /** @var Paragraph[] $paragraphs */
    $paragraphs = $results->getModels();

    $pagination = new Pagination(
        [
            'totalCount' => $results->getTotalCount(),
            'defaultPageSize' => Yii::$app->params['context']['pageSize'],
            'pageSizeLimit' => Yii::$app->params['context']['pageSizeLimit'],
        ]
    );
    ?>
    <div class="row">
        <div class="col-md-12">
          <?php if ($pagination->pageCount > 1): ?>
            <div class="container container-pagination d-print-none">
                <div class="detachable">
                    <?php echo LinkPager::widget(
                        [
                            'pagination' => $pagination,
                            'firstPageLabel' => true,
                            'lastPageLabel' => false,
                            'maxButtonCount' => 5,
                            'options' => [
                                'class' => 'd-flex justify-content-center'
                            ],
                            'listOptions' => ['class' => 'pagination mb-0']
                        ]
                    ); ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="card pt-3 my-3">
                <div class="card-body p-0">
                    <div class="px-xl-5 px-lg-5 px-md-5 px-sm-3 paragraph">
                        <?php foreach ($paragraphs as $number => $paragraph): ?>
                            <div id="<?= $paragraph->chunk; ?>" data-entity-id="<?= $paragraph->id; ?>"
                                class="<?= $fragment == $paragraph->chunk ? "card border-secondary" : "" ?>">
                                <div class="card-body">

                                    <div class="paragraph-text">
                                          <?= SearchResultHelper::highlightFieldContent($paragraph, 'content', 'markdown', false); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-start book-name pt-4">
                            <div><strong><i><?= $bookName; ?></i></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container container-pagination d-print-none">
                <div class="detachable">
                    <?php echo LinkPager::widget(
                        [
                            'pagination' => $pagination,
                            'firstPageLabel' => true,
                            'lastPageLabel' => false,
                            'maxButtonCount' => 5,
                            'options' => [
                                'class' => 'd-flex justify-content-center'
                            ],
                            'listOptions' => ['class' => 'pagination mb-0']
                        ]
                    ); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->render('_theme-toggler'); ?>
<?= ScrollWidget::widget(['data_entity_id' => isset($paragraph) ? $paragraph->id : 0]); ?>