// library-search.js
/**
 * Основной модуль для работы с поиском в библиотеке
 * Включает: поиск по авторам, названиям, жанрам с индикацией загрузки
 */

document.addEventListener('DOMContentLoaded', function () {
    // =============================================
    // 1. ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ И ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // =============================================

    // Константы для пустых значений
    const SearchHelper = {
        EMPTY_GENRE: "Не установлен",
        EMPTY_AUTHOR: "Не установлен"
    };

    // Класс для работы с URL
    class UrlHelper {
        static addSearchParam(paramName, paramValue, route = 'library/search') {
            const baseUrl = new URL(window.location.origin);
            const newUrl = new URL(route, baseUrl);
            const searchParams = new URLSearchParams();

            // Копируем существующие параметры (кроме search)
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (!key.startsWith('search[')) searchParams.set(key, value);
            });

            // Собираем параметры search
            const search = {};
            new URLSearchParams(window.location.search).forEach((value, key) => {
                if (key.startsWith('search[') && key.endsWith(']')) {
                    const param = key.match(/search\[(.*?)\]/)[1];
                    search[param] = value;
                }
            });

            // Обновляем параметр
            if (paramValue && paramValue !== SearchHelper.EMPTY_AUTHOR) {
                search[paramName] = paramValue;
            } else {
                delete search[paramName];
            }

            // Добавляем обновленные параметры
            for (const [key, value] of Object.entries(search)) {
                if (value) searchParams.set(`search[${key}]`, value);
            }

            newUrl.search = searchParams.toString();
            return newUrl.toString();
        }
    }

    // Форматирование чисел (разделители тысяч)
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    // Экранирование HTML
    function escapeHtml(unsafe) {
        const div = document.createElement('div');
        div.textContent = unsafe;
        return div.innerHTML;
    }

    // =============================================
    // 2. ОБЩИЕ ФУНКЦИИ ДЛЯ ВСЕХ ТИПОВ ПОИСКА
    // =============================================

    /**
     * Настройка поиска с индикатором загрузки
     * @param {string} inputSelector - Селектор поля ввода
     * @param {string} loaderSelector - Селектор индикатора загрузки
     * @param {function} searchFunction - Функция поиска
     * @param {function} resetFunction - Функция сброса
     */
    function setupSearchWithLoader(inputSelector, loaderSelector, searchFunction, resetFunction) {
        const input = document.querySelector(inputSelector);
        const loader = document.querySelector(loaderSelector);
        let searchTimer;
        let loaderTimer;

        if (!input || !loader) return;

        input.addEventListener('input', function () {
            clearTimeout(searchTimer);
            clearTimeout(loaderTimer);

            const searchText = this.value.trim();

            if (searchText === '') {
                resetFunction();
                return;
            }

            // Показываем индикатор через 500мс если запрос еще выполняется
            loaderTimer = setTimeout(() => {
                loader.classList.remove('d-none');
            }, 300);

            searchTimer = setTimeout(() => {
                if (loader.classList.contains('d-none')) {
                    loader.classList.remove('d-none');
                }
                searchFunction(searchText);
            }, 300);
        });
    }

    // =============================================
    // 3. УПРАВЛЕНИЕ АККОРДЕОНАМИ И ВИДАМИ
    // =============================================

    // Восстановление открытого аккордеона
    const lastOpenAccordion = localStorage.getItem('lastOpenAccordion');
    if (lastOpenAccordion) {
        const collapseElement = document.querySelector(lastOpenAccordion);
        if (collapseElement) {
            new bootstrap.Collapse(collapseElement, { toggle: true });
        }
    }

    // Обработчики для аккордеона
    document.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('click', function () {
            const target = this.getAttribute('data-bs-target');
            localStorage.setItem('lastOpenAccordion', target);
        });
    });

    // Переключение между видами жанров
    const genreInlineCheckbox = document.getElementById('genre-inline-view');
    if (genreInlineCheckbox) {
        const savedView = localStorage.getItem('genreViewMode');
        if (savedView === 'horizontal') {
            genreInlineCheckbox.checked = true;
            toggleGenreView(true);
        }

        genreInlineCheckbox.addEventListener('change', function () {
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

    // Поиск по жанрам (клиентская фильтрация)
    document.querySelectorAll('.genre-search-input').forEach(input => {
        input.addEventListener('keyup', function () {
            const searchText = this.value.toLowerCase();
            const container = this.closest('.accordion-body').querySelector('.genre-list-container');
            const items = container.querySelectorAll('.genre-item');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    item.style.display = '';
                    item.classList.remove('d-none');
                } else {
                    item.style.display = 'none';
                    item.classList.add('d-none');
                }
            });
        });
    });

    // =============================================
    // 4. ПОИСК ПО АВТОРАМ С ИНДИКАТОРОМ ЗАГРУЗКИ
    // =============================================

    const authorSearchInput = document.querySelector('#authorCollapse .facet-search input');
    const authorList = document.querySelector('#authorCollapse .facet-list');
    const authorBadge = document.querySelector('#authorAccordion .accordion-header .badge.bg-secondary');
    const authorLoader = document.querySelector('#authorCollapse .loading-indicator');

    if (authorSearchInput && authorList && authorBadge && authorLoader) {
        const originalAuthors = authorList.innerHTML;
        const originalCount = authorBadge.textContent.trim();

        // Функция сброса
        const resetAuthorList = () => {
            authorBadge.textContent = originalCount;
            authorList.innerHTML = originalAuthors;
            authorLoader.classList.add('d-none');
        };

        // Настройка поиска
        setupSearchWithLoader(
            '#authorCollapse .facet-search input',
            '#authorCollapse .loading-indicator',
            searchAuthors,
            resetAuthorList
        );

        // Функция поиска
        function searchAuthors(query) {
            fetch(`/library/author?q=${encodeURIComponent(query)}`)
                .then(response => {
                    authorLoader.classList.add('d-none');
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    updateAuthorList(data.authors);
                })
                .catch(error => {
                    console.error('Ошибка при поиске авторов:', error);
                    resetAuthorList();
                });
        }

        // Обновление списка
        function updateAuthorList(authorsData) {
            authorBadge.textContent = formatNumber(authorsData.count);
            authorList.innerHTML = '';

            authorsData.data.author_group.buckets.forEach(author => {
                const li = document.createElement('li');
                const key = author.key || SearchHelper.EMPTY_AUTHOR;

                li.innerHTML = `
          <a href="${UrlHelper.addSearchParam('author', key)}">
            ${escapeHtml(key)}
            <span class="badge bg-secondary float-end">${formatNumber(author.doc_count)}</span>
          </a>
        `;
                authorList.appendChild(li);
            });
        }
    }

    // =============================================
    // 5. ПОИСК ПО НАЗВАНИЯМ С ИНДИКАТОРОМ ЗАГРУЗКИ
    // =============================================

    const titleSearchInput = document.querySelector('#titleCollapse .facet-search input');
    const titleList = document.querySelector('#titleCollapse .facet-list');
    const titleBadge = document.querySelector('#titleAccordion .accordion-header .badge.bg-secondary');
    const titleLoader = document.querySelector('#titleCollapse .loading-indicator');

    if (titleSearchInput && titleList && titleBadge && titleLoader) {
        const originalTitles = titleList.innerHTML;
        const originalCount = titleBadge.textContent.trim();

        // Функция сброса
        const resetTitleList = () => {
            titleBadge.textContent = originalCount;
            titleList.innerHTML = originalTitles;
            titleLoader.classList.add('d-none');
        };

        // Настройка поиска
        setupSearchWithLoader(
            '#titleCollapse .facet-search input',
            '#titleCollapse .loading-indicator',
            searchTitles,
            resetTitleList
        );

        // Функция поиска
        function searchTitles(query) {
            fetch(`/library/title?q=${encodeURIComponent(query)}`)
                .then(response => {
                    titleLoader.classList.add('d-none');
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    updateTitleList(data.titles);
                })
                .catch(error => {
                    console.error('Ошибка при поиске названий:', error);
                    resetTitleList();
                });
        }

        // Обновление списка
        function updateTitleList(titlesData) {
            titleBadge.textContent = formatNumber(titlesData.count);
            titleList.innerHTML = '';

            titlesData.data.title_group.buckets.forEach(title => {
                if (!title.key) return;

                const li = document.createElement('li');
                const displayText = title.key.length > 255 ?
                    title.key.substring(0, 255) + '...' :
                    title.key;

                li.innerHTML = `
          <a href="${UrlHelper.addSearchParam('title', title.key)}">
            ${escapeHtml(displayText)}
            <span class="badge bg-secondary float-end">${formatNumber(title.doc_count)}</span>
          </a>
        `;
                titleList.appendChild(li);
            });
        }
    }
});