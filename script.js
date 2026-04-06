// Поиск с выдвигающейся панелью
const searchIcon = document.getElementById('searchIcon');
const searchPanel = document.getElementById('searchPanel');
const closeSearchPanel = document.getElementById('closeSearchPanel');
const overlay = document.getElementById('overlay');
const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');
const recentList = document.getElementById('recentList');
const clearRecentBtn = document.getElementById('clearRecent');

// Загрузка истории поиска
let searchHistory = JSON.parse(localStorage.getItem('searchHistory')) || [];

function updateRecentList() {
    if (!recentList) return;
    
    if (searchHistory.length === 0) {
        recentList.innerHTML = '<div style="color: #ccc; font-size: 13px; text-align: center; padding: 20px;">Пусто</div>';
        if (clearRecentBtn) clearRecentBtn.style.display = 'none';
        return;
    }
    
    if (clearRecentBtn) clearRecentBtn.style.display = 'block';
    
    recentList.innerHTML = searchHistory.slice(0, 5).map((query, index) => `
        <div class="recent-item" data-query="${escapeHtml(query)}">
            <span>🔍 ${escapeHtml(query)}</span>
            <button class="delete-recent" data-index="${index}">&times;</button>
        </div>
    `).join('');
    
    // Добавляем обработчики для недавних запросов
    document.querySelectorAll('.recent-item').forEach(item => {
        const query = item.dataset.query;
        item.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-recent')) {
                // Удаление из истории
                const idx = parseInt(e.target.dataset.index);
                searchHistory.splice(idx, 1);
                localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
                updateRecentList();
                e.stopPropagation();
            } else {
                // Использование запроса
                if (searchInput) searchInput.value = query;
                if (searchForm) searchForm.submit();
            }
        });
    });
}

function addToSearchHistory(query) {
    if (!query || query.trim() === '') return;
    
    // Удаляем дубликаты
    searchHistory = searchHistory.filter(q => q !== query);
    // Добавляем в начало
    searchHistory.unshift(query);
    // Оставляем только 10 последних
    searchHistory = searchHistory.slice(0, 10);
    localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
    updateRecentList();
}

// Открытие поиска
if (searchIcon) {
    searchIcon.addEventListener('click', () => {
        searchPanel.classList.add('active');
        overlay.classList.add('active');
        setTimeout(() => {
            if (searchInput) searchInput.focus();
        }, 100);
    });
}

// Закрытие поиска
if (closeSearchPanel) {
    closeSearchPanel.addEventListener('click', () => {
        searchPanel.classList.remove('active');
        overlay.classList.remove('active');
    });
}

// Закрытие по клику на фон
if (overlay) {
    overlay.addEventListener('click', () => {
        searchPanel?.classList.remove('active');
        cartDrawer?.classList.remove('active');
        overlay.classList.remove('active');
    });
}

// Отправка формы поиска
if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
        const query = searchInput.value.trim();
        if (query) {
            addToSearchHistory(query);
        }
    });
}

// Инициализация истории
updateRecentList();

// Очистка истории
if (clearRecentBtn) {
    clearRecentBtn.addEventListener('click', () => {
        searchHistory = [];
        localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
        updateRecentList();
    });
}

// ESC для закрытия
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        searchPanel?.classList.remove('active');
        cartDrawer?.classList.remove('active');
        overlay?.classList.remove('active');
    }
});



document.addEventListener('DOMContentLoaded', () => {
    // Находим все нужные элементы
    const searchBtn = document.querySelectorAll('.icon-btn')[0];
    const cartBtn = document.querySelectorAll('.icon-btn')[1];
    const searchOverlay = document.getElementById('searchOverlay');
    const cartDrawer = document.getElementById('cartDrawer');
    const bodyOverlay = document.getElementById('bodyOverlay');
    const closeSearch = document.getElementById('closeSearch');
    const closeCart = document.getElementById('closeCart');

    // Логика открытия ПОИСКА
    searchBtn.onclick = () => {
        searchOverlay.classList.add('active');
        searchOverlay.querySelector('input').focus();
    };

    // Логика открытия КОРЗИНЫ
    cartBtn.onclick = () => {
        cartDrawer.classList.add('active');
        bodyOverlay.classList.add('active');
    };

    // Закрытие при клике на крестики или фон
    const closeAll = () => {
        searchOverlay.classList.remove('active');
        cartDrawer.classList.remove('active');
        bodyOverlay.classList.remove('active');
    };

    closeSearch.onclick = closeAll;
    closeCart.onclick = closeAll;
    bodyOverlay.onclick = closeAll;

    // Закрытие по кнопке ESC
    document.onkeydown = (e) => {
        if (e.key === 'Escape') closeAll();
    };
});