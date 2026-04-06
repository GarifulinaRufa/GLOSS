<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_role = $_SESSION['admin_role'];

// Обработка удаления товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Получаем имя товара для лога
    $product = fetchOne("SELECT name FROM products WHERE id = $id");
    
    query("DELETE FROM products WHERE id = $id");
    
    // Логируем действие
    query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Удален товар: {$product['name']}')");
    
    header('Location: products.php?msg=deleted');
    exit();
}

// Получаем список товаров с категориями
$products = fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $message = 'Товар успешно добавлен!';
    if ($_GET['msg'] == 'updated') $message = 'Товар успешно обновлен!';
    if ($_GET['msg'] == 'deleted') $message = 'Товар удален!';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Товары</h1>
                <div class="user-info">
                    <a href="product-add.php" class="btn btn-primary">+ Добавить товар</a>
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Фото</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Остаток</th>
                            <th>Продано</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?php echo $product['id']; ?></td>
                            <td>
                                <?php if ($product['image'] && file_exists('uploads/' . $product['image'])): ?>
                                    <img src="uploads/<?php echo $product['image']; ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: var(--bg-hover); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">Нет</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $product['name']; ?></strong></td>
                            <td><?php echo $product['category_name'] ?? 'Без категории'; ?></td>
                            <td><?php echo number_format($product['price'], 0, '', ' '); ?> $</td>
                            <td style="<?php echo $product['stock'] < 10 ? 'color: var(--danger); font-weight: 700;' : ''; ?>">
                                <?php echo $product['stock']; ?> шт.
                            </td>
                            <td><?php echo $product['sold']; ?> шт.</td>
                            <td>
                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Редактировать</a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить товар?')">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($products) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Нет товаров. <a href="product-add.php">Добавить первый товар</a></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>