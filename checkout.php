<?php
session_start();
require_once 'admin/config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'Корзина пуста']);
    exit();
}

$name = $data['name'];
$phone = $data['phone'];
$address = $data['address'];
$items = $data['items'];

// Начинаем транзакцию
mysqli_begin_transaction($conn);

try {
    // Вычисляем общую сумму
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Проверяем, есть ли пользователь с таким именем
    $user_id = null;
    $check_user = fetchOne("SELECT id FROM users WHERE name = '$name'");
    if ($check_user) {
        $user_id = $check_user['id'];
    } else {
        // Создаем нового пользователя
        $temp_password = uniqid();
        query("INSERT INTO users (name, Password, phone, address) VALUES ('$name', '$temp_password', '$phone', '$address')");
        $user_id = mysqli_insert_id($conn);
    }
    
    // Создаем заказ
    $sql = "INSERT INTO orders (user_id, total, address, phone, status) 
            VALUES ($user_id, $total, '$address', '$phone', 'new')";
    query($sql);
    $order_id = mysqli_insert_id($conn);
    
    // Добавляем товары в заказ и обновляем остатки
    foreach ($items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        
        // Добавляем в order_items
        query("INSERT INTO order_items (order_id, product_id, quantity, price) 
               VALUES ($order_id, $product_id, $quantity, $price)");
        
        // Обновляем остаток и проданные единицы
        query("UPDATE products 
               SET stock = stock - $quantity, 
                   sold = sold + $quantity 
               WHERE id = $product_id");
    }
    
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>