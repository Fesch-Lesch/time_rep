<?php
// --- КОНФИГУРАЦИЯ БАЗЫ 1 (Основная: Пользователи, Студенты) ---
define('DB_HOST', '134.90.167.42');
define('DB_PORT', '10306'); // Порт первой базы
define('DB_NAME', 'project_Feschenko');
define('DB_USER', 'Feschenko');
define('DB_PASS', 'E1dO7]_Ev)sbamOd');
define('DB_CHARSET', 'utf8mb4');

// --- КОНФИГУРАЦИЯ БАЗЫ 2 (Бестиарий) ---
// Заполните данными от второго сервера
define('DB2_HOST', '134.90.167.42'); 
define('DB2_PORT', '10306'); // Порт второй базы (обычно 3306)
define('DB2_NAME', 'project_Noskov');
define('DB2_USER', 'Noskov');
define('DB2_PASS', 'jquMhDL*hrwQEqLu');
define('DB2_CHARSET', 'utf8mb4');

// --- БАЗА 3: КАРТИНКИ (Изображения BLOB) ---
define('DB3_HOST', '134.90.167.42'); // IP сервера с картинками
define('DB3_PORT', '10306'); 
define('DB3_NAME', 'project_Anosov'); // Имя базы с картинками
define('DB3_USER', 'Anosov');
define('DB3_PASS', 'SGL.I8b9zwDY.78h');
define('DB3_CHARSET', 'utf8mb4');

// Вспомогательная функция для создания подключения
function createConnection($host, $port, $name, $user, $pass, $charset) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        die("Ошибка подключения к базе $name: " . $e->getMessage());
    }
}

// Функция подключения к ПЕРВОЙ базе (Основная)
function getDBConnection() {
    return createConnection(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET);
}

// Функция подключения ко ВТОРОЙ базе (Бестиарий)
function getSecondDBConnection() {
    return createConnection(DB2_HOST, DB2_PORT, DB2_NAME, DB2_USER, DB2_PASS, DB2_CHARSET);
}

// Функция подключения к ТРЕТЬЕЙ базе (Картинки)
function getImageDBConnection() {
    return createConnection(DB3_HOST, DB3_PORT, DB3_NAME, DB3_USER, DB3_PASS, DB3_CHARSET);
}

?>