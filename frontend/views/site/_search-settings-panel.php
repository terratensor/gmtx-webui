<?php


use yii\bootstrap5\Html;
use src\Search\forms\SearchForm;
use src\Search\helpers\UrlHelper;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var SearchForm $model */

$aggs = $this->params['aggs'] ?? [];
?>
<div id="search-setting-panel"
    class="search-setting-panel <?= Yii::$app->session->get('show_search_settings') ? 'show-search-settings' : '' ?>">
    <div class="sidebar">
        <div class="sidebar-header d-flex justify-content-between">
            <div>
                <small>Найдено записей: <b><?= number_format($aggs['hits']['total'] ?? 0, 0, '', ' ') ?></b></small>
            </div>
            <button type="button" id="close-search-settings" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="sidebar-content">
            <?= $form->field($model, 'matching', ['inline' => true, 'options' => ['class' => 'pb-2 pt-1']])
                ->radioList($model->getMatching(), ['class' => 'form-check-inline'])
                ->label(false); ?>
            <!-- Чекбокс для включения/выключения нечёткого поиска -->
            <?= $form->field($model, 'fuzzy', ['options' => ['class' => '']])
                ->checkbox()
                ->label('Нечёткий поиск'); ?>
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
            ], false)->label('Компактный вид жанров (компактный вид фильтров?)');
            ?>
            <?php if (!empty($aggs)) : ?>
                <div class="accordion" id="filtersAccordion">
                    <!-- Жанры -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#genreCollapse">
                                <i class="bi bi-bookmark me-2"></i> Жанры
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
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#authorCollapse">
                                <i class="bi bi-person me-2"></i> Авторы
                            </button>
                        </h2>
                        <div id="authorCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
                            <div class="accordion-body p-0">
                                <div class="facet-search mb-2 px-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Поиск авторов...">
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
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Названия -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#titleCollapse">
                                <i class="bi bi-card-text me-2"></i> Названия
                            </button>
                        </h2>
                        <div id="titleCollapse" class="accordion-collapse collapse" data-bs-parent="#filtersAccordion">
                            <div class="accordion-body p-0">
                                <div class="facet-search mb-2 px-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Поиск названий...">
                                </div>
                                <ul class="facet-list">
                                    <?php foreach ($aggs['aggregations']['title_group']['buckets'] as $title): ?>
                                        <?php if (!empty($title['key'])): ?>
                                            <li>
                                                <a href="<?= UrlHelper::addSearchParam('title', $title['key']) ?>">
                                                    <?= Html::encode(mb_substr($title['key'], 0, 30) . (mb_strlen($title['key']) > 30 ? '...' : '')) ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Восстанавливаем открытый аккордеон
        const lastOpenAccordion = localStorage.getItem('lastOpenAccordion');
        if (lastOpenAccordion) {
            const collapseElement = document.querySelector(lastOpenAccordion);
            if (collapseElement) {
                new bootstrap.Collapse(collapseElement, {
                    toggle: true
                });
            }
        }

        // Обработчики для аккордеона
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-bs-target');
                localStorage.setItem('lastOpenAccordion', target);
            });
        });

        // Переключение между видами жанров
        const genreInlineCheckbox = document.getElementById('genre-inline-view');
        if (genreInlineCheckbox) {
            // Восстановление состояния из localStorage
            const savedView = localStorage.getItem('genreViewMode');
            if (savedView === 'horizontal') {
                genreInlineCheckbox.checked = true;
                toggleGenreView(true);
            }

            genreInlineCheckbox.addEventListener('change', function() {
                const isInline = this.checked;
                toggleGenreView(isInline);
                localStorage.setItem('genreViewMode', isInline ? 'horizontal' : 'vertical');
            });
        }

        function toggleGenreView(isInline) {
            const verticalList = document.querySelector('.genre-list-vertical');
            const horizontalList = document.querySelector('.genre-list-horizontal');

            if (isInline) {
                verticalList.classList.add('d-none');
                horizontalList.classList.remove('d-none');
            } else {
                verticalList.classList.remove('d-none');
                horizontalList.classList.add('d-none');
            }
        }

        // Поиск по жанрам (работает в обоих режимах)
        document.querySelectorAll('.genre-search-input').forEach(input => {
            input.addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                const container = this.closest('.accordion-body').querySelector('.genre-list-container');

                // Обрабатываем оба варианта отображения
                const items = container.querySelectorAll('.genre-item');

                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchText)) {
                        item.style.display = '';
                        // Для компактного режима добавляем класс видимости
                        item.classList.remove('d-none');
                    } else {
                        item.style.display = 'none';
                        // Для компактного режима добавляем класс скрытия
                        item.classList.add('d-none');
                    }
                });
            });
        });
    });
</script>