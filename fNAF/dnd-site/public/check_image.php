<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<pre>";

try {
    $pdo = getImageDBConnection();
    
    // 1. Проверяем подключение
    echo "✅ Подключение к БД успешно\n\n";
    
    // 2. Проверяем запись 'back'
    $stmt = $pdo->prepare("SELECT name, image_type, LENGTH(image) as size FROM IMAGES WHERE name = 'back'");
    $stmt->execute();
    $image = $stmt->fetch();
    
    if (!$image) {
        die("❌ Запись с name='back' не найдена");
    }
    
    echo "✅ Картинка 'back' найдена:\n";
    echo "   - Тип: {$image['image_type']}\n";
    echo "   - Размер: {$image['size']} байт\n\n";
    
    // 3. Получаем само изображение
    $stmt = $pdo->prepare("SELECT image FROM IMAGES WHERE name = 'back'");
    $stmt->execute();
    $row = $stmt->fetch();
    
    if (!$row || empty($row['image'])) {
        die("❌ Изображение пустое или не существует");
    }
    
    $imageData = $row['image'];
    echo "✅ Данные изображения получены\n";
    
    // 4. Проверяем первые байты (магические числа форматов)
    $magicBytes = bin2hex(substr($imageData, 0, 4));
    echo "   - Первые 4 байта (hex): $magicBytes\n";
    
    // Определяем формат по магическим числам
    $formats = [
        'ffd8ff' => 'JPEG',
        '89504e47' => 'PNG',
        '47494638' => 'GIF',
        '52494646' => 'WEBP',
        '424d' => 'BMP'
    ];
    
    foreach ($formats as $magic => $format) {
        if (strpos($magicBytes, $magic) === 0) {
            echo "   - Определён формат: $format\n";
            $detectedFormat = strtolower($format);
            break;
        }
    }
    
    if (!isset($detectedFormat)) {
        echo "   - ❌ Неизвестный формат изображения!\n";
        echo "   - Возможно данные повреждены или не являются изображением\n";
    }
    
    // 5. Пробуем создать изображение GD
    $gdImage = @imagecreatefromstring($imageData);
    if ($gdImage) {
        echo "✅ GD успешно распознал изображение\n";
        echo "   - Ширина: " . imagesx($gdImage) . "px\n";
        echo "   - Высота: " . imagesy($gdImage) . "px\n";
        imagedestroy($gdImage);
    } else {
        echo "❌ GD не смог распознать изображение\n";
        echo "   - Возможно данные повреждены\n";
    }
    
    // 6. Показываем превью
    echo "\n📸 Превью изображения:\n";
    echo "<img src='data:image/jpeg;base64," . base64_encode($imageData) . "' style='max-width: 400px; border: 2px solid red;'>\n";
    
    // 7. Сохраняем локально для проверки
    file_put_contents(__DIR__ . '/debug_image.jpg', $imageData);
    echo "\n💾 Изображение сохранено как: " . __DIR__ . "/debug_image.jpg\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}

echo "</pre>";
?>