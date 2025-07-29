<?php

declare(strict_types=1);

use yii\data\Pagination;
use yii\bootstrap5\LinkPager;
use frontend\widgets\ScrollWidget;
use src\Search\helpers\SearchResultHelper;
use src\Library\manticore\models\Paragraph;
use src\Library\manticore\repositories\ParagraphDataProvider;
ini_set('memory_limit', '256M'); // или больше, если нужно

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
                    <?= $paragraph->content; ?>
                    <?php // SearchResultHelper::highlightFieldContent($paragraph, 'content', 'markdown', false); ?>
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
      <div class="progress mb-3 d-print-none sticky-bottom">
        <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
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

<?php
$js = <<<JS
$(document).ready(function() {
    // Функция для обновления URL без перезагрузки страницы
    function updateUrlFragment(fragment) {
        if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('f', fragment);
            history.replaceState(null, '', url.toString());
        }
    }

    // Оптимизированная версия getVisibleFragment
    function getVisibleFragment() {
        const windowHeight = $(window).height();
        const scrollTop = $(window).scrollTop();
        const scrollBottom = scrollTop + windowHeight;
        
        // Бинарный поиск по позиции прокрутки
        const fragments = $('.paragraph > div[id]');
        let low = 0;
        let high = fragments.length - 1;
        let bestMatch = null;
        let bestMatchDiff = Infinity;
        
        while (low <= high) {
            const mid = Math.floor((low + high) / 2);
            const fragment = $(fragments[mid]);
            const fragmentTop = fragment.offset().top;
            const fragmentBottom = fragmentTop + fragment.height();
            const fragmentMiddle = fragmentTop + fragment.height() / 2;
            
            // Проверяем, находится ли середина фрагмента в видимой области
            if (fragmentMiddle >= scrollTop && fragmentMiddle <= scrollBottom) {
                return fragment.attr('id');
            }
            
            // Ищем ближайший фрагмент
            const diffTop = Math.abs(fragmentTop - scrollTop);
            const diffBottom = Math.abs(fragmentBottom - scrollBottom);
            const minDiff = Math.min(diffTop, diffBottom);
            
            if (minDiff < bestMatchDiff) {
                bestMatchDiff = minDiff;
                bestMatch = fragment.attr('id');
            }
            
            if (fragmentBottom < scrollTop) {
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }
        
        return bestMatch;
    }

    // Обработчик прокрутки с троттлингом
    let scrollTimeout;
    $(window).scroll(function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            const visibleFragment = getVisibleFragment();
            if (visibleFragment) {
                updateUrlFragment(visibleFragment);
                // Подсветка текущего фрагмента
                // $('.paragraph > div').removeClass('card border-secondary');
                // $('#' + visibleFragment).addClass('card border-secondary');
            }
        }, 150); // Задержка в 150мс
    });

    // Инициализация при загрузке
    const initialFragment = '$fragment';
    if (initialFragment && $('#' + initialFragment).length) {
        $('#' + initialFragment).addClass('card border-secondary');
    }

    // Обновление прогресса чтения
    function updateReadingProgress() {
        const fragments = $('.paragraph > div[id]');
        const totalHeight = $(document).height() - $(window).height();
        const progress = ($(window).scrollTop() / totalHeight) * 100;
        $('.progress-bar').css('width', progress + '%').attr('aria-valuenow', progress);
    }

    $(window).scroll(updateReadingProgress);
    $(document).ready(updateReadingProgress);


    // Сохранение позиции при закрытии страницы
    $(window).on('beforeunload', function() {
        const visibleFragment = getVisibleFragment();
        if (visibleFragment) {
            localStorage.setItem('lastReadFragment_' + window.location.pathname, visibleFragment);
        }
    });

    // Восстановление позиции при загрузке (если нет параметра f в URL)
    if (!window.location.href.includes('f=')) {
        const lastFragment = localStorage.getItem('lastReadFragment_' + window.location.pathname);
        if (lastFragment && $('#' + lastFragment).length) {
            $('html, body').animate({
                scrollTop: $('#' + lastFragment).offset().top - 20
            }, 300);
            updateUrlFragment(lastFragment);
        }
    }
});
JS;

$this->registerJs($js);
?>