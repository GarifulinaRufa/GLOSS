<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Получаем статистику
// Общее количество товаров
$products_count = fetchOne("SELECT COUNT(*) as total FROM products")['total'];

// Общее количество заказов
$orders_count = fetchOne("SELECT COUNT(*) as total FROM orders")['total'];

// Общая выручка
$total_revenue = fetchOne("SELECT SUM(total) as total FROM orders WHERE status != 'cancelled'")['total'] ?? 0;

// Количество пользователей
$users_count = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'user'")['total'];

// Заказы за сегодня
$today = date('Y-m-d');
$today_orders = fetchOne("SELECT COUNT(*) as total, SUM(total) as revenue FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled'") ?? ['total' => 0, 'revenue' => 0];

// Последние 5 заказов
$recent_orders = fetchAll("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");

// Товары с низким остатком (менее 10)
$low_stock = fetchAll("SELECT * FROM products WHERE stock < 10 AND stock > 0 ORDER BY stock ASC LIMIT 5");

// Популярные товары (по количеству продаж)
$popular_products = fetchAll("SELECT p.id, p.name, p.sold, p.price FROM products p ORDER BY p.sold DESC LIMIT 5");

// Статистика по статусам заказов
$status_stats = fetchAll("SELECT status, COUNT(*) as count FROM orders GROUP BY status");

// Статистика по категориям
$category_stats = fetchAll("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin| GLOSS&SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Админ-панель</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo $admin_name; ?></span>
                    <span class="user-role"><?php echo $admin_role == 'admin' ? 'Администратор' : 'Менеджер'; ?></span>
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Товары</div>
                    <div class="stat-value"><?php echo $products_count; ?></div>
                    <div class="stat-change">в каталоге</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Заказы</div>
                    <div class="stat-value"><?php echo $orders_count; ?></div>
                    <div class="stat-change">всего</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Выручка</div>
                    <div class="stat-value"><?php echo number_format($total_revenue, 0, '', ' '); ?> сом</div>
                    <div class="stat-change">за все время</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Пользователи</div>
                    <div class="stat-value"><?php echo $users_count; ?></div>
                    <div class="stat-change">зарегистрировано</div>
                </div>
            </div>
            
            <!-- Заказы за сегодня -->
            <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="stat-card">
                    <div class="stat-title">Заказы сегодня</div>
                    <div class="stat-value"><?php echo $today_orders['total']; ?></div>
                    <div class="stat-change">на сумму <?php echo number_format($today_orders['revenue'] ?? 0, 0, '', ' '); ?> сом</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Средний чек</div>
                    <div class="stat-value"><?php echo $orders_count > 0 ? number_format($total_revenue / $orders_count, 0, '', ' ') : 0; ?> сом</div>
                    <div class="stat-change">по всем заказам</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Статусы заказов -->
                <div class="table-container" style="padding: 20px;">
                    <h3 style="margin-bottom: 20px;">Статусы заказов</h3>
                    <?php foreach ($status_stats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span class="status-badge status-<?php echo $stat['status']; ?>">
                                <?php 
                                    $status_names = [
                                        'new' => 'Новые',
                                        'processing' => 'В обработке',
                                        'shipped' => 'Отправлены',
                                        'delivered' => 'Доставлены',
                                        'cancelled' => 'Отменены'
                                    ];
                                    echo $status_names[$stat['status']] ?? $stat['status'];
                                ?>
                            </span>
                            <span style="font-weight: 700;"><?php echo $stat['count']; ?> шт.</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Товары по категориям -->
                <div class="table-container" style="padding: 20px;">
                    <h3 style="margin-bottom: 20px;">Товары по категориям</h3>
                    <?php foreach ($category_stats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span><?php echo $stat['name'] ?? 'Без категории'; ?></span>
                            <span style="font-weight: 700;"><?php echo $stat['count']; ?> товаров</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Последние заказы -->
            <div class="table-container" style="margin-bottom: 32px;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h3>Последние заказы</h3>
                    <a href="orders.php" class="btn btn-secondary btn-sm">Все заказы →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Покупатель</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['user_name'] ?? 'Гость'; ?></td>
                            <td><?php echo number_format($order['total'], 0, '', ' '); ?> сом</td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                        $status_names = [
                                            'new' => 'Новый',
                                            'processing' => 'В обработке',
                                            'shipped' => 'Отправлен',
                                            'delivered' => 'Доставлен',
                                            'cancelled' => 'Отменен'
                                        ];
                                        echo $status_names[$order['status']];
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm">Просмотр</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($recent_orders) == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Нет заказов</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Товары с низким остатком -->
                <div class="table-container">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border);">
                        <h3>⚠️ Товары с низким остатком</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Остаток</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td style="color: var(--danger); font-weight: 700;"><?php echo $product['stock']; ?> шт.</td>
                                <td>
                                    <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Пополнить</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($low_stock) == 0): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Все товары в наличии</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Популярные товары -->
                <div class="table-container">
                    <div style="padding: 20px; border-bottom: 1px solid var(--border);">
                        <h3>🔥 Популярные товары</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Продано</th>
                                <th>Выручка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_products as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['sold']; ?> шт.</td>
                                <td><?php echo number_format($product['sold'] * $product['price'], 0, '', ' '); ?> сом</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($popular_products) == 0): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Нет данных</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>