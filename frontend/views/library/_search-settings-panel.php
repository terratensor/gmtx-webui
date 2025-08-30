<?php

use yii\bootstrap5\Html;
use src\Search\forms\SearchForm;
use src\Search\helpers\UrlHelper;
use src\Search\helpers\SearchHelper;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var SearchForm $model */

$aggs = $this->params['aggs'] ?? [];
$summary = '';
if ($total_count) {
  $summary = "<small>Всего записей в библиотеке: <b>" . number_format($total_count ?? 0, 0, '', ' ') . "</b></small>";
} else {
  $summary = "<small>Найдено записей: <b>" . number_format($aggs['hits']['total'] ?? 0, 0, '', ' ') . "</b></small>";
}

?>
<div id="search-setting-panel"
  class="search-setting-panel <?= Yii::$app->session->get('show_search_settings') ? 'show-search-settings' : '' ?>">
  <div class="sidebar">
    <!--
    <div class="sidebar-header d-flex justify-content-between">
      <div>
        <?= $summary ?>
      </div>
      <button type="button" id="close-search-settings" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div> -->

    <div class="sidebar-content">
      <?= $form->field($model, 'matching', ['inline' => true, 'options' => ['class' => 'pb-2 pt-1']])
        ->radioList($model->getMatching(), ['class' => 'form-check-inline'])
        ->label(false); ?>
      <?php if (Yii::$app->params['fuzzy_search_enabled']): ?>
        <!-- Чекбокс для включения/выключения нечёткого поиска -->
        <?= $form->field($model, 'fuzzy', ['options' => ['class' => '']])
          ->checkbox()
          ->label('Нечёткий поиск'); ?>
      <?php endif; ?>
      <!-- Чекбокс для включения/выключения однострочного режима -->
      <?= $form->field($model, 'singleLineMode', [
        'options' => ['class' => 'single-line-mode'],
        'template' => "<div class=\"form-check form-switch\">\n{input}\n{label}\n</div>",
        'labelOptions' => ['class' => 'form-check-label'],
      ])->checkbox([
        'class' => 'form-check-input',
        'id' => 'single-line-mode',
        'uncheck' => null,
        'data-scroll' => 'true',
      ], false)->label('Однострочный режим (убрать переносы строк)');
      ?>
      <!-- Чекбокс для переключения вида жанров -->
      <?= $form->field($model, 'genreInlineView', [
        'options' => ['class' => 'pb-2 genre-inline-view'],
        'template' => "<div class=\"form-check form-switch\">\n{input}\n{label}\n</div>",
        'labelOptions' => ['class' => 'form-check-label'],
      ])->checkbox([
        'class' => 'form-check-input',
        'id' => 'genre-inline-view',
        'uncheck' => null,
      ], false)->label('Компактный вид жанров');
      ?>
      <?php if (!empty($aggs)) : ?>
        <div class="accordion" id="filtersAccordion">
          <!-- Жанры -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#genreCollapse">
                <i class="bi bi-bookmark me-2"></i> Жанры <span class="badge bg-secondary"></span>
              </button>
            </h2>
            <div id="genreCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
              <div class="accordion-body p-0">
                <div class="facet-search mb-2 px-2">
                  <input type="text" class="form-control form-control-sm genre-search-input" placeholder="Поиск жанров...">
                </div>
                <div class="genre-list-container">
                  <!-- Список жанров (по умолчанию) -->
                  <ul class="facet-list genre-list-vertical scrollable-container">
                    <?php foreach ($aggs['aggregations']['genre_group']['buckets'] as $genre): ?>
                      <?php if (!empty($genre['key'])): ?>
                        <li class="genre-item">
                          <a href="<?= UrlHelper::addSearchParam('genre', $genre['key']) ?>">
                            <?= Html::encode($genre['key']) ?>
                            <span class="badge bg-secondary float-end"><?= number_format($genre['doc_count'], 0, '', ' ') ?></span>
                          </a>
                        </li>
                      <?php else: ?>
                        <li class="genre-item">
                          <a href="<?= UrlHelper::addSearchParam('genre', SearchHelper::EMPTY_GENRE) ?>">
                            <?= Html::encode(SearchHelper::EMPTY_GENRE) ?>
                            <span class="badge bg-secondary float-end"><?= number_format($genre['doc_count'], 0, '', ' ') ?></span>
                          </a>
                        </li>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </ul>
                  <!-- Компактный вид жанров (скрыт по умолчанию) -->
                  <div class="genre-list-horizontal d-none scrollable-container">
                    <div class="genre-tags-container">
                      <?php foreach ($aggs['aggregations']['genre_group']['buckets'] as $genre): ?>
                        <?php if (!empty($genre['key'])): ?>
                          <a href="<?= UrlHelper::addSearchParam('genre', $genre['key']) ?>" class="genre-tag genre-item">
                            <?= Html::encode($genre['key']) ?>
                            <span class="badge bg-secondary"><?= number_format($genre['doc_count'], 0, '', ' ') ?></span>
                          </a>
                        <?php else: ?>
                          <a href="<?= UrlHelper::addSearchParam('genre', SearchHelper::EMPTY_GENRE) ?>" class="genre-tag genre-item">
                            <?= Html::encode(SearchHelper::EMPTY_GENRE) ?>
                            <span class="badge bg-secondary"><?= number_format($genre['doc_count'], 0, '', ' ') ?></span>
                          </a>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Остальные аккордеоны (авторы, названия) остаются без изменений -->
          <!-- Авторы -->
          <div class="accordion-item" id="authorAccordion">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#authorCollapse">
                <i class="bi bi-person me-2"></i> Авторы <span class="badge bg-secondary author-badge"></span>
              </button>
            </h2>
            <div id="authorCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
              <div class="accordion-body p-0">
                <div class="facet-search mb-2 px-2 position-relative">
                  <input type="text" class="form-control form-control-sm" placeholder="Начните вводить имя для поиска…">
                  <div class="position-absolute top-50 end-0 translate-middle-y loading-indicator d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="visually-hidden">Загрузка...</span>
                    </div>
                  </div>
                </div>
                <ul class="facet-list">
                  <?php foreach ($aggs['aggregations']['author_group']['buckets'] as $author): ?>
                    <?php if (!empty($author['key'])): ?>
                      <li>
                        <a href="<?= UrlHelper::addSearchParam('author', $author['key']) ?>">
                          <?= Html::encode($author['key']) ?>
                          <span class="badge bg-secondary float-end"><?= number_format($author['doc_count'], 0, '', ' ') ?></span>
                        </a>
                      </li>
                    <?php else: ?>
                      <li>
                        <a href="<?= UrlHelper::addSearchParam('author', SearchHelper::EMPTY_AUTHOR) ?>">
                          <?= Html::encode(SearchHelper::EMPTY_AUTHOR) ?>
                          <span class="badge bg-secondary float-end"><?= number_format($author['doc_count'], 0, '', ' ') ?></span>
                        </a>
                      </li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>

          <!-- Названия -->
          <div class="accordion-item" id="titleAccordion">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#titleCollapse">
                <i class="bi bi-card-text me-2"></i> Названия <span class="badge bg-secondary title-badge"></span>
              </button>
            </h2>
            <div id="titleCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
              <div class="accordion-body p-0">
                <div class="facet-search mb-2 px-2 position-relative">
                  <input type="text" class="form-control form-control-sm" placeholder="Начните вводить название для поиска…">
                  <div class="position-absolute top-50 end-0 translate-middle-y loading-indicator d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="visually-hidden">Загрузка...</span>
                    </div>
                  </div>
                </div>
                <ul class="facet-list">
                  <?php foreach ($aggs['aggregations']['title_group']['buckets'] as $title): ?>
                    <?php if (!empty($title['key'])): ?>
                      <li>
                        <a href="<?= UrlHelper::addSearchParam('title', $title['key']) ?>">
                          <?= Html::encode(mb_substr($title['key'], 0, 250) . (mb_strlen($title['key']) > 250 ? '...' : '')) ?>
                          <span class="badge bg-secondary float-end"><?= number_format($title['doc_count'], 0, '', ' ') ?></span>
                        </a>
                      </li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $this->registerJsFile('/js/library-search.js', ['position' => \yii\web\View::POS_END]);
