<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$id = (int)$_GET['id'];
$order = fetchOne("
    SELECT o.*, u.name as user_name, u.email, u.phone, u.address 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $id
");

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Получаем товары в заказе
$items = fetchAll("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = $id
");

$statuses = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменен'
];

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = escape($_POST['status']);
    query("UPDATE orders SET status = '$new_status' WHERE id = $id");
    
    // Логируем
    query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Изменен статус заказа #$id на $new_status')");
    
    header('Location: order-view.php?id=' . $id . '&msg=updated');
    exit();
}

$message = '';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $message = 'Статус заказа обновлен!';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #<?php echo $id; ?> | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Заказ #<?php echo $id; ?></h1>
                <div class="user-info">
                    <a href="orders.php" class="btn btn-secondary">← Назад к заказам</a>
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Информация о заказе -->
                <div class="table-container" style="padding: 24px;">
                    <h3 style="margin-bottom: 20px;">Информация о заказе</h3>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Дата заказа</div>
                        <div><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Статус</div>
                        <div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo $statuses[$order['status']]; ?>
                            </span>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Итого</div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo number_format($order['total'], 0, '', ' '); ?> сом</div>
                    </div>
                    
                    <form method="POST" style="margin-top: 24px;">
                        <div class="form-group">
                            <label>Изменить статус</label>
                            <div style="display: flex; gap: 12px;">
                                <select name="status" style="flex: 1;">
                                    <?php foreach ($statuses as $key => $name): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $order['status'] == $key ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Обновить</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Информация о покупателе -->
                <div class="table-container" style="padding: 24px;">
                    <h3 style="margin-bottom: 20px;">Покупатель</h3>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Имя</div>
                        <div><?php echo $order['user_name'] ?? 'Гость'; ?></div>
                    </div>
                    <?php if ($order['email']): ?>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Email</div>
                        <div><?php echo $order['email']; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['phone']): ?>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Телефон</div>
                        <div><?php echo $order['phone']; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['address']): ?>
                    <div style="margin-bottom: 16px;">
                        <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 4px;">Адрес доставки</div>
                        <div><?php echo $order['address']; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Товары в заказе -->
            <div class="table-container">
                <div style="padding: 20px; border-bottom: 1px solid var(--border);">
                    <h3>Товары в заказе</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Цена</th>
                            <th>Кол-во</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                                        <img src="uploads/<?php echo $item['image']; ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                                    <?php endif; ?>
                                    <strong><?php echo $item['name']; ?></strong>
                                </div>
                            </td>
                            <td><?php echo number_format($item['price'], 0, '', ' '); ?> сом</td>
                            <td><?php echo $item['quantity']; ?> шт.</td>
                            <td><?php echo number_format($item['quantity'] * $item['price'], 0, '', ' '); ?> сом</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--bg-hover);">
                            <td colspan="3" style="text-align: right; font-weight: 700;">Итого:</td>
                            <td style="font-weight: 800; font-size: 18px;"><?php echo number_format($order['total'], 0, '', ' '); ?> сом</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </main>
    </div>
</body>
</html>