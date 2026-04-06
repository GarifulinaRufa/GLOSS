<?php
// admin/config/db.php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'my_sate';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8');

// Функция для выполнения запросов
function query($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}

// Функция для получения одной строки
function fetchOne($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Функция для получения всех строк
function fetchAll($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Функция для безопасности
function escape($str) {
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}
?>