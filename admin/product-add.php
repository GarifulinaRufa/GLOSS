<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Получаем категории для выбора
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($_POST['name']);
    $price = (float)$_POST['price'];
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : 'NULL';
    $description = escape($_POST['description']);
    $stock = (int)$_POST['stock'];
    
    // Обработка загрузки фото
   // Обработка загрузки фото
$image_name = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($extension, $allowed)) {
        $image_name = time() . '_' . uniqid() . '.' . $extension;
        
        // Поднимаемся на уровень выше из папки admin и заходим в uploads
        // Это гарантирует, что папка будет в корне сайта
        $upload_dir = '../uploads/'; 
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_path = $upload_dir . $image_name;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $error = 'Ошибка перемещения файла в ' . $upload_path;
        }
    } else {
        $error = 'Недопустимый формат файла.';
    }
}
    
    if (!$error) {
        $sql = "INSERT INTO products (name, price, category_id, description, stock, sold, image) 
                VALUES ('$name', $price, $category_id, '$description', $stock, 0, " . ($image_name ? "'$image_name'" : "NULL") . ")";
        
        if (query($sql)) {
            $product_id = mysqli_insert_id($conn);
            
            // Логируем действие
            query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Добавлен товар: $name')");
            
            header('Location: products.php?msg=added');
            exit();
        } else {
            $error = 'Ошибка добавления товара: ' . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Добавить товар</h1>
                <div class="user-info">
                    <a href="products.php" class="btn btn-secondary">← Назад</a>
                    <a href="logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="form-card">
                <div class="form-group">
                    <label>Название товара *</label>
                    <input type="text" name="name" required placeholder="Например: Nike Air Max">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Цена (сом) *</label>
                        <input type="number" name="price" step="0.01" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Категория</label>
                        <select name="category_id">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Количество на складе *</label>
                        <input type="number" name="stock" required value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Фото товара</label>
                        <input type="file" name="image" accept="image/*">
                        <small style="color: var(--text-secondary); display: block; margin-top: 5px;">Рекомендуемый размер: 500x500px. Поддерживаются: JPG, PNG, WEBP</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание товара</label>
                    <textarea name="description" rows="5" placeholder="Подробное описание товара..."></textarea>
                </div>
                
                <div style="display: flex; gap: 16px; justify-content: flex-end;">
                    <a href="products.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Сохранить товар</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>