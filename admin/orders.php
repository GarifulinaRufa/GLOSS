<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Обработка изменения статуса
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = escape($_POST['status']);
    
    query("UPDATE orders SET status = '$status' WHERE id = $order_id");
    
    // Логируем
    query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Изменен статус заказа #$order_id на $status')");
    
    header('Location: orders.php?msg=updated');
    exit();
}

// Получаем список заказов
$where = [];
if (isset($_GET['status']) && $_GET['status']) {
    $status = escape($_GET['status']);
    $where[] = "o.status = '$status'";
}
if (isset($_GET['search']) && $_GET['search']) {
    $search = escape($_GET['search']);
    $where[] = "(o.id LIKE '%$search%' OR u.name LIKE '%$search%')";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$orders = fetchAll("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    $where_sql
    ORDER BY o.created_at DESC
");

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $message = 'Статус заказа обновлен!';
}

$statuses = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменен'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Заказы</h1>
                <div class="user-info">
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Фильтры -->
            <div class="table-container" style="margin-bottom: 24px; padding: 20px;">
                <form method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Статус</label>
                        <select name="status">
                            <option value="">Все</option>
                            <?php foreach ($statuses as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo isset($_GET['status']) && $_GET['status'] == $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Поиск</label>
                        <input type="text" name="search" placeholder="ID заказа или имя" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Фильтр</button>
                    <a href="orders.php" class="btn btn-secondary">Сбросить</a>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Покупатель</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['user_name'] ?? 'Гость'; ?></td>
                            <td><?php echo number_format($order['total'], 0, '', ' '); ?> сом</td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo $statuses[$order['status']]; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm">Просмотр</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($orders) == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Нет заказов</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>