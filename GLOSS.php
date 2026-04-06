<?php
session_start();
require_once 'admin/config/db.php';

// Функция для получения пути к фото (та же, что в catalog.php)
function getProductImageUrl($image_name) {
    if (empty($image_name)) {
        return 'img/placeholder.png';
    }
    
    $paths = ['admin/uploads/', 'uploads/'];
    
    foreach ($paths as $path) {
        if (file_exists(__DIR__ . '/' . $path . $image_name)) {
            return $path . $image_name;
        }
    }
    
    return 'img/placeholder.png';
}

// Получаем популярные товары для показа на главной
$popular_products = fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.sold DESC 
    LIMIT 4
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLOSS & SPORT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для корзины и поиска (как в catalog.php) */
        .search-panel {
            position: fixed;
            top: 0;
            right: -100%;
            width: 320px;
            height: 100vh;
            background: #fff;
            z-index: 2000;
            transition: 0.3s ease;
            box-shadow: -5px 0 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .search-panel.active {
            right: 0;
        }
        
        .search-panel-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-panel-header h3 {
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }
        
        .search-panel-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .search-panel-form {
            padding: 24px;
        }
        
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input-wrapper input {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            background: #f8f8f8;
        }
        
        .search-input-wrapper input:focus {
            outline: none;
            border-color: #CCFF00;
            background: #fff;
        }
        
        .search-input-wrapper button {
            position: absolute;
            right: 8px;
            background: #CCFF00;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-recent {
            padding: 0 24px;
            flex: 1;
        }
        
        .search-recent h4 {
            font-size: 11px;
            font-weight: 500;
            color: #999;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #666;
            cursor: pointer;
            padding: 6px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .recent-item:hover {
            color: #CCFF00;
        }
        
        .delete-recent {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #ccc;
        }
        
        .clear-recent {
            margin-top: 20px;
            background: none;
            border: none;
            font-size: 11px;
            color: #ff4444;
            cursor: pointer;
            padding: 8px 0;
        }
        
        /* Корзина */
        .cart-drawer {
            position: fixed;
            top: 0;
            right: -380px;
            width: 380px;
            height: 100vh;
            background: #fff;
            z-index: 2000;
            transition: 0.3s ease;
            box-shadow: -5px 0 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .cart-drawer.active {
            right: 0;
        }
        
        .cart-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h3 {
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }
        
        .close-cart {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
        }
        
        .cart-empty {
            text-align: center;
            color: #999;
            font-size: 13px;
            margin-top: 60px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .cart-item-price {
            font-size: 12px;
            color: #888;
        }
        
        .cart-item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1px solid #e0e0e0;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        
        .cart-item-total {
            font-size: 13px;
            font-weight: 600;
            min-width: 70px;
            text-align: right;
        }
        
        .remove-item {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #ccc;
            margin-left: 12px;
        }
        
        .cart-footer {
            padding: 20px 24px;
            border-top: 1px solid #f0f0f0;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .checkout-btn {
            width: 100%;
            background: #000;
            color: #CCFF00;
            border: none;
            padding: 14px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            cursor: pointer;
        }
        
        .overlay-dark {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1999;
            opacity: 0;
            pointer-events: none;
            transition: 0.3s;
        }
        
        .overlay-dark.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #CCFF00;
            color: #000;
            font-size: 10px;
            font-weight: bold;
            border-radius: 50%;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
        }
        
        /* Популярные товары на главной */
        .popular-section {
            padding: 60px 0;
            background: #f5f5f5;
        }
        
        .popular-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .popular-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }
        
        .popular-card .img-box {
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 12px;
        }
        
        .popular-card .img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .popular-card .name {
            font-size: 16px;
            font-weight: 700;
            margin: 15px 0 5px;
        }
        
        .popular-card .price {
            font-size: 14px;
            color: #666;
        }
        
        .section-title-center {
            text-align: center;
            font-size: 32px;
            margin-bottom: 40px;
            letter-spacing: 3px;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header__container">
        <div class="logo" style="font-size: 60px;">
            <span class="logo-white">GLOSS</span> <span style="color:#CCFF00">&</span> <span class="logo-gray">SPORT</span>
        </div>

        <nav class="nav">
            <ul class="nav__list">
                <li><a href="GLOSS.php" class="nav__link active">Главная</a></li>
                <li><a href="catalog.php?new=1" class="nav__link">Новинки</a></li>
                <li><a href="catalog.php" class="nav__link">Каталог</a></li>
            </ul>
        </nav>

        <div class="header__actions">
            <button class="icon-btn" id="searchBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            <button class="icon-btn" id="cartBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                    <path d="M3 6h18"></path>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="cart-count" id="cartCount">0</span>
            </button>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero__container">
        <div class="hero__content">
            <h1>MOVE BEYOND<br>LIMITS</h1>
            <p>Высокоэффективное снаряжение для спортсменов, тренирующихся с неустанной сосредоточенностью.</p>
            <a href="catalog.php" class="btn-main">SHOP NOW</a>
        </div>
    </div>
    <div class="hero__overlay"></div>
</section>

<!-- Популярные товары -->
<?php if (count($popular_products) > 0): ?>
<section class="popular-section">
    <div class="container">
        <h2 class="section-title-center">ПОПУЛЯРНЫЕ ТОВАРЫ</h2>
        <div class="popular-grid">
            <?php foreach ($popular_products as $product): ?>
                <div class="popular-card">
                    <div class="img-box">
                        <?php 
                        $image_url = getProductImageUrl($product['image']);
                        ?>
                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="price"><?php echo number_format($product['price'], 0, '', ' '); ?> сом</div>
                    <button class="add-to-cart" 
                            data-id="<?php echo $product['id']; ?>"
                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-price="<?php echo $product['price']; ?>"
                            <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>
                            style="margin-top: 15px; padding: 8px 20px; background: #CCFF00; border: none; border-radius: 30px; cursor: pointer;">
                        <?php echo $product['stock'] == 0 ? 'Нет в наличии' : 'В корзину'; ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="store-light">
    <div class="container store__grid">
        <div class="store__info">
            <h2 class="section-title">Sporting Store</h2>
            <div class="info-item">
                <span class="label-gray">LOCATION:</span>
                <p>г. Каракол, Гагарина 127 📍</p>
            </div>
            <div class="info-item">
                <span class="label-gray">VISITING HOURS:</span>
                <p>MON — SUN: 10:00 — 22:00</p>
            </div>
            <a href="#" class="btn-outline-dark" id="showMapBtn">ПОКАЗАТЬ НА КАРТЕ</a>
        </div>
        <div class="map-container" id="mapContainer">
            <div class="map-overlay-white"></div>
            <div id="map" style="width: 100%; height: 400px; border-radius: 12px;"></div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container footer__grid">
        <div class="footer__col">
            <div class="logo-footer">GLOSS <span>& SPORT</span></div>
            <p class="copy">© 2026. GLOSS & SPORT<br>ALL RIGHTS RESERVED</p>
        </div>
        <div class="footer__col center">
            <span class="footer-label">CUSTOMER SERVICE</span>
            <div class="contact-row">
            <span >Есть вопросы? Наш бот поможет!</span>
            <a href="https://wa.me/14155238886?text=join%20compass-dream" target="_blank" class="bot-button">
                💬 Написать в WhatsApp
            </a>
        </div>
                <span class="phone">+996 (XXX) XX-XX-XX</span>
            </div>
        </div>
        <div class="footer__col right">
            <span class="footer-label">LOCATION</span>
            <p class="loc-text">KARAKOL, GAGARINA 127</p>
        </div>
    </div>
</footer>

<!-- Панель поиска -->
<div class="search-panel" id="searchPanel">
    <div class="search-panel-header">
        <h3>ПОИСК</h3>
        <button class="search-panel-close" id="closeSearchPanel">&times;</button>
    </div>
    <div class="search-panel-form">
        <form method="GET" action="catalog.php" id="searchForm">
            <div class="search-input-wrapper">
                <input type="text" name="search" placeholder="Что вы ищете?" id="searchInput" autocomplete="off">
                <button type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>
    <div class="search-recent" id="searchRecent">
        <h4>НЕДАВНИЕ ЗАПРОСЫ</h4>
        <div class="recent-list" id="recentList"></div>
        <button class="clear-recent" id="clearRecent" style="display: none;">Очистить историю</button>
    </div>
</div>

<!-- Корзина -->
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <h3>КОРЗИНА</h3>
        <button class="close-cart" id="closeCart">&times;</button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty">В корзине пусто</div>
    </div>
    <div class="cart-footer" id="cartFooter" style="display: none;">
        <div class="cart-total">
            <span>ИТОГО:</span>
            <span id="cartTotal">0 сом</span>
        </div>
        <button class="checkout-btn" id="checkoutBtn">ОФОРМИТЬ ЗАКАЗ</button>
    </div>
</div>

<div class="overlay-dark" id="overlay"></div>

<script src="https://api-maps.yandex.ru/2.1/?apikey=ваш_ключ&lang=ru_RU"></script>
<script>
// ========== КОРЗИНА (та же логика, что в catalog.php) ==========
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
    updateCartCount();
}

function updateCartCount() {
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElem = document.getElementById('cartCount');
    if (cartCountElem) cartCountElem.textContent = count;
}

function updateCartUI() {
    const cartItemsDiv = document.getElementById('cartItems');
    const cartFooter = document.getElementById('cartFooter');
    const cartTotalSpan = document.getElementById('cartTotal');
    
    if (!cartItemsDiv) return;
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<div class="cart-empty">В корзине пусто</div>';
        if (cartFooter) cartFooter.style.display = 'none';
        return;
    }
    
    if (cartFooter) cartFooter.style.display = 'block';
    
    let html = '';
    let total = 0;
    
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-price">${item.price.toLocaleString()} сом</div>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <div class="cart-item-total">${itemTotal.toLocaleString()} сом</div>
                <button class="remove-item" onclick="removeItem(${index})">&times;</button>
            </div>
        `;
    });
    
    cartItemsDiv.innerHTML = html;
    if (cartTotalSpan) cartTotalSpan.textContent = total.toLocaleString() + ' сом';
}

function escapeHtml(str) {
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function updateQuantity(index, change) {
    if (cart[index]) {
        cart[index].quantity += change;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        saveCart();
    }
}

function removeItem(index) {
    cart.splice(index, 1);
    saveCart();
}

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    saveCart();
    
    const btn = document.querySelector(`.add-to-cart[data-id="${id}"]`);
    if (btn) {
        const originalText = btn.textContent;
        btn.textContent = '✓';
        setTimeout(() => {
            btn.textContent = originalText;
        }, 800);
    }
}

// ========== ИСТОРИЯ ПОИСКА ==========
let searchHistory = JSON.parse(localStorage.getItem('searchHistory')) || [];

function updateRecentList() {
    const recentList = document.getElementById('recentList');
    const clearRecentBtn = document.getElementById('clearRecent');
    if (!recentList) return;
    
    if (searchHistory.length === 0) {
        recentList.innerHTML = '<div style="color: #ccc; font-size: 12px; text-align: center; padding: 20px;">Пусто</div>';
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
    
    document.querySelectorAll('.recent-item').forEach(item => {
        const query = item.dataset.query;
        item.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-recent')) {
                const idx = parseInt(e.target.dataset.index);
                searchHistory.splice(idx, 1);
                localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
                updateRecentList();
                e.stopPropagation();
            } else {
                const searchInput = document.getElementById('searchInput');
                const searchForm = document.getElementById('searchForm');
                if (searchInput) searchInput.value = query;
                if (searchForm) searchForm.submit();
            }
        });
    });
}

function addToSearchHistory(query) {
    if (!query || query.trim() === '') return;
    searchHistory = searchHistory.filter(q => q !== query);
    searchHistory.unshift(query);
    searchHistory = searchHistory.slice(0, 10);
    localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
    updateRecentList();
}

// ========== КАРТА ==========
function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;
    
    // Координаты Каракола, Гагарина 127
    const karakolCoords = [42.4907, 78.3936];
    
    const map = new ymaps.Map('map', {
        center: karakolCoords,
        zoom: 16,
        controls: ['zoomControl', 'fullscreenControl']
    });
    
    // Добавляем метку
    const placemark = new ymaps.Placemark(karakolCoords, {
        hintContent: 'GLOSS & SPORT',
        balloonContent: '<strong>GLOSS & SPORT</strong><br>г. Каракол, Гагарина 127'
    }, {
        preset: 'islands#greenDotIcon'
    });
    
    map.geoObjects.add(placemark);
}

// ========== ИНИЦИАЛИЗАЦИЯ ==========
document.addEventListener('DOMContentLoaded', function() {
    // Корзина
    updateCartUI();
    updateCartCount();
    
    // Кнопки добавления в корзину
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseInt(this.dataset.price);
            addToCart(id, name, price);
        });
    });
    
    // Поиск
    const searchBtn = document.getElementById('searchBtn');
    const searchPanel = document.getElementById('searchPanel');
    const closeSearchPanel = document.getElementById('closeSearchPanel');
    const overlay = document.getElementById('overlay');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchPanel.classList.add('active');
            overlay.classList.add('active');
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 100);
        });
    }
    
    if (closeSearchPanel) {
        closeSearchPanel.addEventListener('click', function() {
            searchPanel.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Корзина
    const cartBtn = document.getElementById('cartBtn');
    const cartDrawer = document.getElementById('cartDrawer');
    const closeCart = document.getElementById('closeCart');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cartBtn) {
        cartBtn.addEventListener('click', function() {
            cartDrawer.classList.add('active');
            overlay.classList.add('active');
        });
    }
    
    if (closeCart) {
        closeCart.addEventListener('click', function() {
            cartDrawer.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Затемнение
    if (overlay) {
        overlay.addEventListener('click', function() {
            searchPanel.classList.remove('active');
            cartDrawer.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Поиск с сохранением истории
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            const query = searchInput.value.trim();
            if (query) {
                addToSearchHistory(query);
            }
        });
    }
    
    // Очистка истории
    const clearRecentBtn = document.getElementById('clearRecent');
    if (clearRecentBtn) {
        clearRecentBtn.addEventListener('click', function() {
            searchHistory = [];
            localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
            updateRecentList();
        });
    }
    
    // ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchPanel.classList.remove('active');
            cartDrawer.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
    
    // Оформление заказа
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async function() {
            if (cart.length === 0) {
                alert('Корзина пуста');
                return;
            }
            
            const name = prompt('Введите ваше имя:');
            if (!name) return;
            const phone = prompt('Введите номер телефона:');
            if (!phone) return;
            const address = prompt('Введите адрес доставки:');
            if (!address) return;
            
            try {
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, phone, address, items: cart })
                });
                const result = await response.json();
                if (result.success) {
                    alert(`Заказ #${result.order_id} оформлен!`);
                    cart = [];
                    saveCart();
                    cartDrawer.classList.remove('active');
                    overlay.classList.remove('active');
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Ошибка оформления заказа');
            }
        });
    }
    
    // Инициализация истории
    updateRecentList();
    
    // Карта
    const showMapBtn = document.getElementById('showMapBtn');
    if (showMapBtn) {
        showMapBtn.addEventListener('click', function(e) {
            e.preventDefault();
            ymaps.ready(initMap);
        });
    }
    
    // Загружаем карту сразу
    ymaps.ready(initMap);
});
</script>
</body>
</html>