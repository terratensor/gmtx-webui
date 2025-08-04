<?php

declare(strict_types=1);

use yii\bootstrap5\Html;
use src\Search\helpers\UrlHelper;
use src\Search\helpers\SearchHelper;

$this->params['meta_description'] = 'Библиотека';

$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);

/** Quote form block  */
// var_dump($results); die();
echo Html::beginForm(['/site/quote'], 'post', ['name' => 'QuoteForm',  'target' => "print_blank"]);
echo Html::hiddenInput('uuid', '', ['id' => 'quote-form-uuid']);
echo Html::endForm();

?>
<div class="site-index">
  <?= $this->render('_search-panel', ['model' => $model]); ?>
  <div class="container-fluid search-results">


    <?php if ($errorQueryMessage): ?>
      <div class="card border-danger mb-3">
        <div class="card-body"><?= $errorQueryMessage; ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($results)) : ?>
      <div class="accordion" id="filtersAccordion">
        <!-- Жанры -->
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#genreCollapse">
              <i class="bi bi-bookmark me-2"></i> Жанры <span class="badge bg-danger"><?= number_format($results['genres']['count'], 0, '', ' ') ?></span>
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

                  <?php foreach ($results['genres']['data'] as $genre): ?>
                    <?php if (!empty($genre['genre'])): ?>
                      <li class="genre-item">
                        <a href="<?= UrlHelper::addSearchParam('genre', $genre['genre']) ?>">
                          <?= Html::encode($genre['genre']) ?>
                          <span class="badge bg-secondary float-end"><?= number_format($genre['count(*)'], 0, '', ' ') ?></span>
                        </a>
                      </li>
                    <?php else: ?>
                      <li class="genre-item">
                        <a href="<?= UrlHelper::addSearchParam('genre', SearchHelper::EMPTY_GENRE) ?>">
                          <?= Html::encode(SearchHelper::EMPTY_GENRE) ?>
                          <span class="badge bg-secondary float-end"><?= number_format($genre['count(*)'], 0, '', ' ') ?></span>
                        </a>
                      </li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
                <!-- Компактный вид жанров (скрыт по умолчанию) -->
                <div class="genre-list-horizontal d-none scrollable-container">
                  <div class="genre-tags-container">
                    <?php foreach ($results['genres']['data'] as $genre): ?>
                      <?php if (!empty($genre['genre'])): ?>
                        <a href="<?= UrlHelper::addSearchParam('genre', $genre['genre']) ?>" class="genre-tag genre-item">
                          <?= Html::encode($genre['genre']) ?>
                          <span class="badge bg-secondary"><?= number_format($genre['count(*)'], 0, '', ' ') ?></span>
                        </a>
                      <?php else: ?>
                        <a href="<?= UrlHelper::addSearchParam('genre', SearchHelper::EMPTY_GENRE) ?>" class="genre-tag genre-item">
                          <?= Html::encode(SearchHelper::EMPTY_GENRE) ?>
                          <span class="badge bg-secondary"><?= number_format($genre['count(*)'], 0, '', ' ') ?></span>
                        </a>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
                        <?php //var_dump($results['authors']); die(); ?>
        <!-- Авторы -->
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#authorCollapse">
              <i class="bi bi-person me-2"></i> Авторы <span class="badge bg-danger"><?= number_format($results['authors']['count'], 0, '', ' ') ?></span>
            </button>
          </h2>
          <div id="authorCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
            <div class="accordion-body p-0">
              <div class="facet-search mb-2 px-2">
                <input type="text" class="form-control form-control-sm" placeholder="Начните вводить имя для поиска...">
              </div>
              <ul class="facet-list">
                <?php //var_dump($results['authors']['data']['author_group']['buckets']); die(); ?>
                <?php foreach ($results['authors']['data']['author_group']['buckets'] as $author): ?>
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


      </div>
    <?php endif; ?>

  </div>
</div>