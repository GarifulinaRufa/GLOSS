<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$id = (int)$_GET['id'];
$product = fetchOne("SELECT * FROM products WHERE id = $id");

if (!$product) {
    header('Location: products.php');
    exit();
}

$categories = fetchAll("SELECT * FROM categories ORDER BY name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escape($_POST['name']);
    $price = (float)$_POST['price'];
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : 'NULL';
    $description = escape($_POST['description']);
    $stock = (int)$_POST['stock'];
    
    // Обработка загрузки нового фото
    $image_sql = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($extension, $allowed)) {
            $image_name = time() . '_' . uniqid() . '.' . $extension;
            $upload_path = 'uploads/' . $image_name;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Удаляем старое фото
                if ($product['image'] && file_exists('uploads/' . $product['image'])) {
                    unlink('uploads/' . $product['image']);
                }
                $image_sql = ", image = '$image_name'";
            } else {
                $error = 'Ошибка загрузки фото';
            }
        } else {
            $error = 'Разрешены только JPG, PNG, WEBP';
        }
    }
    
    if (!$error) {
        $sql = "UPDATE products SET 
                name = '$name',
                price = $price,
                category_id = $category_id,
                description = '$description',
                stock = $stock
                $image_sql
                WHERE id = $id";
        
        if (query($sql)) {
            // Логируем действие
            query("INSERT INTO admin_logs (admin_id, action) VALUES ({$_SESSION['admin_id']}, 'Отредактирован товар: $name')");
            
            header('Location: products.php?msg=updated');
            exit();
        } else {
            $error = 'Ошибка обновления товара';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать товар | GLOSS & SPORT Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Редактировать товар</h1>
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
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Цена (сом) *</label>
                        <input type="number" name="price" step="0.01" required value="<?php echo $product['price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Категория</label>
                        <select name="category_id">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Количество на складе *</label>
                        <input type="number" name="stock" required value="<?php echo $product['stock']; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Фото товара</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if ($product['image'] && file_exists('uploads/' . $product['image'])): ?>
                            <div class="image-preview">
                                <img src="uploads/<?php echo $product['image']; ?>" alt="">
                                <small>Текущее фото</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание товара</label>
                    <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 16px; justify-content: flex-end;">
                    <a href="products.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>