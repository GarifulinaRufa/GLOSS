<?php
// Подключение к базе
$conn = mysqli_connect("localhost", "root", "", "my_sate");

if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$message = "";
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'login'; // Режим: login или register

// --- ЛОГИКА РЕГИСТРАЦИИ ---
if (isset($_POST['do_register'])) {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $userpassword = $_POST["password"]; // В идеале использовать password_hash

    // Проверка, нет ли уже такого пользователя
    $check = mysqli_query($conn, "SELECT id FROM users WHERE name='$username'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Ошибка: Пользователь с таким именем уже существует!";
        $mode = 'register';
    } else {
        $sql = "INSERT INTO users (name, Password) VALUES('$username', '$userpassword')";
        if (mysqli_query($conn, $sql)) {
            $message = "Аккаунт успешно создан! Теперь войдите.";
            $mode = 'login';
        }
    }
}

// --- ЛОГИКА ВХОДА ---
if (isset($_POST['do_login'])) {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $userpassword = mysqli_real_escape_string($conn, $_POST["password"]);

    $sql = "SELECT * FROM users WHERE name='$username' AND Password='$userpassword'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_fetch_assoc($result)) {
        header("Location: saite.html");
        exit();
    } else {
        $message = "Ошибка: Неверное имя или пароль!";
        $mode = 'login';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация | GLOSS & SPORT</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="regist.css">
    <style>
        /* Базовая стилизация, если CSS не подгрузится */
        .status-msg { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        .toggle-link { cursor: pointer; color: #007bff; text-decoration: underline; display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>GLOSS <span>&</span> SPORT</h1>
                <h3><?php echo ($mode == 'register') ? 'Регистрация' : 'Вход в систему'; ?></h3>
            </div>
            
            <?php if ($message): ?>
                <div class="status-msg <?php echo (strpos($message, 'создан') !== false) ? 'success' : ''; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                
                <div class="form-group">
                    <label>Имя пользователя</label>
                    <input type="text" name="username" required placeholder="Введите имя" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <?php if ($mode == 'register'): ?>
                    <button type="submit" name="do_register" class="btn btn-primary btn-block">Зарегистрироваться</button>
                    <a href="?" class="toggle-link" onclick="event.preventDefault(); document.querySelector('input[name=mode]').value='login'; document.forms[0].submit();">Уже есть аккаунт? Войти</a>
                <?php else: ?>
                    <button type="submit" name="do_login" class="btn btn-primary btn-block"><a href="GLOSS.html">Войти</a></button>
                    <button type="submit" name="change_to_reg" class="toggle-link" style="background:none; border:none; width:100%;" onclick="document.querySelector('input[name=mode]').value='register';">Нет аккаунта? Регистрация</button>
                <?php endif; ?>
            </form>
            
            <div class="login-footer">
                <span class="demo-hint"></span>
            </div>
        </div>
    </div>
</body>
</html>