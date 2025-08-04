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
  <?= $this->render('_search-panel', ['model' => $model, 'total_count' => $results['total_count'] ?? 0]); ?>
  <div class="container-fluid start-searching">


    <?php if ($errorQueryMessage): ?>
      <div class="card border-danger mb-3">
        <div class="card-body"><?= $errorQueryMessage; ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($results)) : ?>
      <p class="summary">Всего записей в библиотеке: <?= number_format($results['total_count'] ?? 0, 0, '', ' '); ?></p>
      <div class="accordion mt-3" id="filtersAccordion">
        <!-- Жанры -->
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#genreCollapse">
              <i class="bi bi-bookmark me-2"></i> Жанры <span class="badge bg-secondary"><?= number_format($results['genres']['count'], 0, '', ' ') ?></span>
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
        <!-- Авторы -->
        <div class="accordion-item" id="authorAccordion">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#authorCollapse">
              <i class="bi bi-person me-2"></i> Авторы <span class="badge bg-secondary author-badge"><?= number_format($results['authors']['count'], 0, '', ' ') ?></span>
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

        <!-- Названия -->
        <div class="accordion-item" id="titleAccordion">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#titleCollapse">
              <i class="bi bi-card-text me-2"></i> Названия <span class="badge bg-secondary title-badge">
                <?= number_format($results['titles']['count'], 0, '', ' ') ?></span>
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
                <?php foreach ($results['titles']['data']['title_group']['buckets'] as $title): ?>
                  <?php if (!empty($title['key'])): ?>
                    <li>
                      <a href="<?= UrlHelper::addSearchParam('title', $title['key']) ?>">
                        <?= Html::encode($title['key']) ?>
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
<?= $this->render('_theme-toggler'); ?>

<?php $js = <<<JS
let menu = $(".search-block");
var menuOffsetTop = menu.offset().top;
var menuHeight = menu.outerHeight();
var menuParent = menu.parent();
var menuParentPaddingTop = parseFloat(menuParent.css("padding-top"));
 
checkWidth();
 
function checkWidth() {
    if (menu.length !== 0) {
      $(window).scroll(onScroll);
    }
}
 
function onScroll() {
  if ($(window).scrollTop() > menuOffsetTop) {
    menu.addClass("shadow");
    menuParent.css({ "padding-top": menuParentPaddingTop });
  } else {
    menu.removeClass("shadow");
    menuParent.css({ "padding-top": menuParentPaddingTop });
  }
}

// Показать/скрыть настройки
const btn = document.getElementById('button-search-settings');
btn.addEventListener('click', toggleSearchSettings, false)

//Кнопка закрыть в настройках
document.getElementById('close-search-settings').addEventListener('click', toggleSearchSettings, false)

function toggleSearchSettings(event) {
  event.preventDefault();
  btn.classList.toggle('active')
  document.getElementById('search-setting-panel').classList.toggle('show-search-settings')
  
  const formData = new FormData(document.forms.searchSettingsForm);
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "/site/search-settings");
  xhr.send(formData);
}
// Обработчик ссылок контекста
const contextButtons = document.querySelectorAll('button.btn-context')
contextButtons.forEach(function (element) {
  element.addEventListener('click', btnContextHandler, false)
})

function btnContextHandler(event) {
  const quoteForm = document.forms["QuoteForm"]
  const uuid = document.getElementById("quote-form-uuid")
  uuid.value = event.target.dataset.uuid
  quoteForm.submit();
}


$('input[type=radio]').on('change', function() {
    $(this).closest("form").submit();
});

JS;

$this->registerJs($js);
?>

<?php
$js = <<<JS
// Функция для определения видимого параграфа с учетом sticky-панели
function getVisibleParagraphId() {
    const paragraphs = document.querySelectorAll('.paragraph');
    const searchBlock = document.querySelector('.search-block');
    const searchBlockHeight = searchBlock ? searchBlock.offsetHeight : 0;
    
    let visibleParagraphId = null;
    let maxVisibleArea = 0;
    
    paragraphs.forEach(paragraph => {
        const rect = paragraph.getBoundingClientRect();
        // Вычисляем видимую высоту с учетом sticky-панели
        const visibleHeight = Math.min(rect.bottom, window.innerHeight) - 
                             Math.max(rect.top, searchBlockHeight);
        
        if (visibleHeight > 0 && visibleHeight > maxVisibleArea) {
            maxVisibleArea = visibleHeight;
            visibleParagraphId = paragraph.dataset.entityId;
        }
    });
    
    return visibleParagraphId || (paragraphs.length > 0 ? paragraphs[0].dataset.entityId : null);
}

// Функция для скролла к параграфу
function scrollToParagraph() {
    const urlParams = new URLSearchParams(window.location.search);
    const paragraphId = urlParams.get('scrollTo');
    
    if (paragraphId) {
        const element = document.querySelector('.paragraph[data-entity-id="' + paragraphId + '"]');
        if (element) {
            setTimeout(() => {
                // Учитываем высоту sticky-панели при скролле
                const searchBlock = document.querySelector('.search-block');
                const offset = searchBlock ? searchBlock.offsetHeight + 20 : 20;
                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                
                window.scrollTo({
                    top: elementPosition - offset,
                    behavior: 'smooth'
                });
                
                // Подсвечиваем параграф
                element.style.transition = 'background-color 0.5s';
                element.style.backgroundColor = '#f8f9fa';
                
                setTimeout(() => {
                    element.style.backgroundColor = '';
                }, 2000);
            }, 100);
            
            // Удаляем параметр из URL
            urlParams.delete('scrollTo');
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, '', newUrl);
        }
    }
}

// Обработчик чекбокса
document.getElementById('single-line-mode').addEventListener('change', function() {
    const visibleParagraphId = getVisibleParagraphId();
    const urlParams = new URLSearchParams(window.location.search);
    
    urlParams.set('search[singleLineMode]', this.checked ? '1' : '0');
    
    if (visibleParagraphId) {
        urlParams.set('scrollTo', visibleParagraphId);
    }
    
    if (urlParams.has('page')) {
        urlParams.set('page', urlParams.get('page'));
    }
    
    window.location.search = urlParams.toString();
});

// Инициализация скролла
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrollToParagraph);
} else {
    scrollToParagraph();
}

$('form').on('submit', function() {
    // Сохраняем текущие значения фильтров перед отправкой
    const searchParams = new URLSearchParams(window.location.search);
    const genre = searchParams.get('search[genre]');
    const author = searchParams.get('search[author]');
    const title = searchParams.get('search[title]');
    
    if (genre) {
        $(this).append('<input type="hidden" name="search[genre]" value="' + genre + '">');
    }
    if (author) {
        $(this).append('<input type="hidden" name="search[author]" value="' + author + '">');
    }
    if (title) {
        $(this).append('<input type="hidden" name="search[title]" value="' + title + '">');
    }
});

// Инициализация tooltips для активных фильтров
$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// Обработчик кликов по кнопкам удаления фильтров
$(document).on('click', '.filter-badge .close', function(e) {
    e.preventDefault();
    window.location.href = $(this).attr('href');
});

   // Обработчик копирования источника
  $('.copy-source').on('click', function() {
    const sourceText = $(this).data('source');
    const tooltip = bootstrap.Tooltip.getInstance(this);
    
    // Создаем временный textarea для копирования
    const textarea = document.createElement('textarea');
    textarea.value = sourceText;
    textarea.style.position = 'fixed';  // Чтобы не было прокрутки страницы
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
      // Пробуем использовать современный API
      if (navigator.clipboard) {
        navigator.clipboard.writeText(sourceText).then(function() {
          showCopiedTooltip(tooltip, this);
        }.bind(this));
      } else {
        // Старый метод для браузеров без Clipboard API
        const success = document.execCommand('copy');
        if (success) {
          showCopiedTooltip(tooltip, this);
        } else {
          throw new Error('Copy command failed');
        }
      }
    } catch (err) {
      console.error('Ошибка копирования:', err);
      // Показываем сообщение об ошибке
      $(this).attr('data-bs-title', 'Ошибка копирования');
      tooltip.show();
      setTimeout(() => {
        $(this).attr('data-bs-title', 'Копировать источник');
        tooltip.hide();
      }, 2000);
    } finally {
      // Удаляем временный элемент
      document.body.removeChild(textarea);
    }
  });
  
  function showCopiedTooltip(tooltip, element) {
    $(element).attr('data-bs-title', 'Скопировано!');
    tooltip.show();
    setTimeout(() => {
      $(element).attr('data-bs-title', 'Копировать источник');
      tooltip.hide();
    }, 2000);
  }

JS;

$this->registerJs($js);
?>