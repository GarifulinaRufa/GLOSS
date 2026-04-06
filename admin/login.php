<?php
session_start();
require_once 'config/db.php';

// Если уже авторизован, перенаправляем на дашборд
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE name = '$username' AND (role = 'admin' OR role = 'manager')";
    $result = query($sql);
    $user = mysqli_fetch_assoc($result);
    
    // Простая проверка пароля (в реальном проекте используйте password_hash)
    if ($user && $user['Password'] === $password) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_role'] = $user['role'];
        
        // Логируем вход
        $admin_id = $user['id'];
        query("INSERT INTO admin_logs (admin_id, action) VALUES ($admin_id, 'Вход в админ-панель')");
        
        header('Location: index.php');
        exit();
    } else {
        $error = 'Неверное имя пользователя или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | GLOSS & SPORT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>GLOSS <span>&</span> SPORT</h1>
                <p>Админ-панель</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Имя пользователя</label>
                    <input type="text" name="username" required placeholder="Введите имя">
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            
            <div class="login-footer">
                <span class="demo-hint"></span>
            </div>
        </div>
    </div>
</body>
</html>