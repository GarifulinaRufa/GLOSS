<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Только админ может управлять пользователями
if ($_SESSION['admin_role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Добавление менеджера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manager'])) {
    $name = escape($_POST['name']);
    $password = escape($_POST['password']);
    $role = escape($_POST['role']);
    
    query("INSERT INTO users (name, Password, role) VALUES ('$name', '$password', '$role')");
    
    header('Location: users.php?msg=added');
    exit();
}

// Удаление пользователя
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $user = fetchOne("SELECT name FROM users WHERE id = $id");
    
    query("DELETE FROM users WHERE id = $id");
    
    header('Location: users.php?msg=deleted');
    exit();
}

$users = fetchAll("SELECT id, name, role, created_at FROM users ORDER BY id DESC");
$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $message = 'Пользователь добавлен!';
    if ($_GET['msg'] == 'deleted') $message = 'Пользователь удален!';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Пользователи</h1>
                <div class="user-info">
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Форма добавления менеджера -->
            <div class="form-card" style="margin-bottom: 32px;">
                <h3 style="margin-bottom: 20px;">Добавить менеджера</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Имя пользователя" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Пароль" required>
                        </div>
                        <div class="form-group">
                            <select name="role">
                                <option value="manager">Менеджер</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                        <button type="submit" name="add_manager" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
            
            <!-- Список пользователей -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Роль</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td>
                                <?php 
                                    if ($user['role'] == 'admin') echo '<span class="status-badge" style="background: rgba(204,255,0,0.1); color: var(--accent);">Админ</span>';
                                    elseif ($user['role'] == 'manager') echo '<span class="status-badge" style="background: rgba(0,204,136,0.1); color: var(--success);">Менеджер</span>';
                                    else echo '<span class="status-badge">Пользователь</span>';
                                ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['role'] != 'admin'): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить пользователя?')">Удалить</a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
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