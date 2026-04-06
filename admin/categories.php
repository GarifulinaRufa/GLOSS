<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Только админ может управлять категориями
if ($_SESSION['admin_role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = escape($_POST['name']);
    $slug = escape(strtolower(str_replace(' ', '-', $name)));
    
    query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')");
    
    // Логируем
    query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Добавлена категория: $name')");
    
    header('Location: categories.php?msg=added');
    exit();
}

// Удаление категории
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $cat = fetchOne("SELECT name FROM categories WHERE id = $id");
    
    query("DELETE FROM categories WHERE id = $id");
    
    // Логируем
    query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Удалена категория: {$cat['name']}')");
    
    header('Location: categories.php?msg=deleted');
    exit();
}

// Редактирование категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = escape($_POST['name']);
    $slug = escape(strtolower(str_replace(' ', '-', $name)));
    
    query("UPDATE categories SET name = '$name', slug = '$slug' WHERE id = $id");
    
    header('Location: categories.php?msg=updated');
    exit();
}

$categories = fetchAll("SELECT * FROM categories ORDER BY id DESC");

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $message = 'Категория добавлена!';
    if ($_GET['msg'] == 'updated') $message = 'Категория обновлена!';
    if ($_GET['msg'] == 'deleted') $message = 'Категория удалена!';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категории | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Категории</h1>
                <div class="user-info">
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Форма добавления -->
            <div class="form-card" style="margin-bottom: 32px;">
                <h3 style="margin-bottom: 20px;">Добавить категорию</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <input type="text" name="name" placeholder="Название категории" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
            
            <!-- Список категорий -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <form method="POST" style="display: inline-flex; gap: 8px;">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" style="width: auto; padding: 6px 12px;">
                                    <button type="submit" name="edit_category" class="btn btn-secondary btn-sm">Сохранить</button>
                                </form>
                            </td>
                            <td><?php echo $cat['slug']; ?></td>
                            <td>
                                <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить категорию?')">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>