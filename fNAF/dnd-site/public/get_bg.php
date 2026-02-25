<?php
// public/get_bg.php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Подключаемся к базе картинок (используя функцию из config/database.php)
    $pdo = getImageDBConnection();

    // 2. Ищем картинку с именем 'back' (или другим, которое вы используете)
    $stmt = $pdo->prepare("SELECT image FROM IMAGES WHERE name = 'back' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['image'])) {
        // 3. Отдаем картинку браузеру
        header("Content-Type: image/jpeg"); // Если png, замените на image/png
        echo $row['image'];
    } else {
        // Если картинка не найдена, отдаем 404
        http_response_code(404);
    }
} catch (Exception $e) {
    http_response_code(500);
}
?>